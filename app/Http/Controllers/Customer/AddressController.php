<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index()
    {
        return view('customer.addresses.index', [
            'addresses' => auth()->user()->addresses()->latest()->get(),
        ]);
    }

    public function store(StoreAddressRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                auth()->user()->addresses()->update(['is_default' => false]);
            }

            auth()->user()->addresses()->create($data);
        });

        return back()->with('success', __('messages.flash.address_saved'));
    }

    public function edit(Address $address)
    {
        $this->authorize('update', $address);

        return view('customer.addresses.edit', compact('address'));
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        // Authorization is enforced in UpdateAddressRequest::authorize().
        $data = $request->validated();

        DB::transaction(function () use ($data, $address) {
            if (! empty($data['is_default'])) {
                auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            // Only the validated address fields — user_id can never be rebound.
            $address->update($data);
        });

        return redirect()->route('customer.addresses.index')
            ->with('success', __('messages.flash.address_saved'));
    }

    /**
     * Promote an existing address to the customer's default. Reuses the same
     * single-default invariant as store()/update(); no new business logic.
     */
    public function makeDefault(Address $address)
    {
        $this->authorize('update', $address);

        DB::transaction(function () use ($address) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', __('messages.flash.address_default_set'));
    }

    public function destroy(Address $address)
    {
        $this->authorize('delete', $address);

        $address->delete();

        return back()->with('success', __('messages.flash.address_removed'));
    }
}
