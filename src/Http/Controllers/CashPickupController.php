<?php

namespace Fintech\Remit\Http\Controllers;

use Exception;
use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Remit\Facades\Remit;
use Fintech\Remit\Http\Requests\ImportCashPickupRequest;
use Fintech\Remit\Http\Requests\IndexCashPickupRequest;
use Fintech\Remit\Http\Requests\StoreCashPickupRequest;
use Fintech\Remit\Http\Requests\UpdateCashPickupRequest;
use Fintech\Remit\Http\Resources\CashPickupCollection;
use Fintech\Remit\Http\Resources\CashPickupResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class CashPickupController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to CashPickup
 *
 * @lrd:end
 */
class CashPickupController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *CashPickup* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexCashPickupRequest $request): CashPickupCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'money_transfer'])->first()->getKey();
            $cashPickupPaginate = Remit::cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *CashPickup* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $depositor = $request->user('sanctum');

            $depositAccount = \Fintech\Transaction\Facades\Transaction::userAccount()->list([
                'user_id' => $user_id ?? $depositor->getKey(),
                'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
            ])->first();

            if (! $depositAccount) {
                throw new Exception("User don't have account deposit balance");
            }

            $masterUser = \Fintech\Auth\Facades\Auth::user()->list([
                'role_name' => SystemRole::MasterUser->value,
                'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
            ])->first();

            if (! $masterUser) {
                throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
            }

            //set pre defined conditions of deposit
            $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'money_transfer'])->first()->getKey();
            $inputs['user_id'] = $user_id ?? $depositor->getKey();
            $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
            if ($delayCheck['countValue'] > 0) {
                throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
            }
            $inputs['sender_receiver_id'] = $masterUser->getKey();
            $inputs['is_refunded'] = false;
            $inputs['status'] = OrderStatus::Successful->value;
            $inputs['risk'] = RiskProfile::Low->value;
            //TODO CONVERTER
            $inputs['converted_amount'] = $inputs['amount'];
            $inputs['converted_currency'] = $inputs['currency'];
            $inputs['order_data']['created_by'] = $depositor->name;
            $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
            $inputs['order_data']['created_at'] = now();
            $inputs['order_data']['master_user_name'] = $masterUser['name'];
            //$inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
            $inputs['order_data']['assign_order'] = 'no';
            $inputs['order_data']['system_notification_variable_success'] = 'cash_pickup_success';
            $inputs['order_data']['system_notification_variable_failed'] = 'cash_pickup_failed';

            $cashPickup = Remit::cashPickup()->create($inputs);

            if (! $cashPickup) {
                throw (new StoreOperationException)->setModel(config('fintech.remit.cash_pickup_model'));
            }

            $order_data = $cashPickup->order_data;
            $order_data['purchase_number'] = entry_number($cashPickup->getKey(), $cashPickup->sourceCountry->iso3, OrderStatus::Successful->value);
            $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($cashPickup);
            $order_data['user_name'] = $cashPickup->user->name;
            Remit::bankTransfer()->debitTransaction($cashPickup);
            $depositedAccount = \Fintech\Transaction\Facades\Transaction::userAccount()->list([
                'user_id' => $depositor->getKey(),
                'country_id' => $cashPickup->source_country_id,
            ])->first();
            $order_data['order_data']['previous_amount'] = $depositedAccount->user_account_data['available_amount'];
            $order_data['order_data']['current_amount'] = ($order_data['order_data']['previous_amount'] + $inputs['amount']);
            //TODO ALL Beneficiary Data with bank and branch data
            $beneficiaryData = Banco::beneficiary()->manageBeneficiaryData($order_data);
            $order_data['order_data']['beneficiary_data'] = $beneficiaryData;

            Remit::bankTransfer()->update($cashPickup->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Cash Pickup']),
                'id' => $cashPickup->id,
            ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *CashPickup* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): CashPickupResource|JsonResponse
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return new CashPickupResource($cashPickup);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *CashPickup* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateCashPickupRequest $request, string|int $id): JsonResponse
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            $inputs = $request->validated();

            if (! Remit::cashPickup()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *CashPickup* resource using id.
     *
     * @lrd:end
     *
     * @return JsonResponse
     *
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (! Remit::cashPickup()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *CashPickup* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $cashPickup = Remit::cashPickup()->find($id, true);

            if (! $cashPickup) {
                throw (new ModelNotFoundException)->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            if (! Remit::cashPickup()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.remit.cash_pickup_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Cash Pickup']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = Remit::cashPickup()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Cash Pickup']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *CashPickup* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return CashPickupCollection|JsonResponse
     */
    public function import(ImportCashPickupRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $cashPickupPaginate = Remit::cashPickup()->list($inputs);

            return new CashPickupCollection($cashPickupPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}