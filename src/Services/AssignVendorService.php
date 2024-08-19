<?php

namespace Fintech\Remit\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Exceptions\RemitException;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    private $serviceVendorModel;

    private MoneyTransfer $serviceVendorDriver;

    /**
     * @throws RemitException
     */
    private function initiateVendor(string $slug): void
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new RemitException(__('remit::messages.assign_vendor.not_found', ['slug' => ucfirst($slug)]));
        }

        $this->serviceVendorModel = \Fintech\Business\Facades\Business::serviceVendor()->list(['service_vendor_slug' => $slug, 'enabled'])->first();

        if (! $this->serviceVendorModel) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $slug);
        }

        $this->serviceVendorDriver = App::make($availableVendors[$slug]['driver']);
    }

    /**
     * @throws \Fintech\Remit\Exceptions\AlreadyAssignedException
     * @throws UpdateOperationException
     */
    public function availableVendors(BaseModel $order, $requestingUserId): Collection
    {
        if ($order->assigned_user_id != null
            && $order->assigned_user_id != $requestingUserId) {
            throw new \Fintech\Remit\Exceptions\AlreadyAssignedException(__('remit::messages.assign_vendor.already_assigned'));
        }

        if ($order->assigned_user_id == null
            && ! Transaction::order()->update($order->getKey(), ['assigned_user_id' => $requestingUserId])) {
            throw new UpdateOperationException(__('remit::messages.assign_vendor.assigned_user_failed'));
        }

        return Business::serviceVendor()->list([
            'service_id_array' => [$order->service_id],
            'enabled' => true,
            'paginate' => false,
        ]);
    }

    /**
     * @throws RemitException|ErrorException
     */
    public function requestQuote(BaseModel $order, string $vendor_slug): mixed
    {
        $this->initiateVendor($vendor_slug);

        return $this->serviceVendorDriver->requestQuote($order);
    }

    /**
     * @throws ErrorException
     * @throws UpdateOperationException|RemitException
     */
    public function processOrder(BaseModel $order, string $vendor_slug): mixed
    {
        $this->initiateVendor($vendor_slug);

        if (! Transaction::order()->update($order->getKey(), [
            'vendor' => $vendor_slug,
            'service_vendor_id' => $this->serviceVendorModel->getKey(),
            'status' => OrderStatus::Processing->value])) {
            throw new UpdateOperationException(__('remit::assign_vendor.failed', ['slug' => $vendor_slug]));
        }

        $order->fresh();

        return $this->serviceVendorDriver->executeOrder($order);
    }

    /**
     * @throws RemitException
     */
    public function orderStatus(BaseModel $order): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('remit::messages.assign_vendor.not_assigned'));
        }

        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    public function amendmentOrder(BaseModel $order): mixed
    {
        $this->initiateVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }
}
