<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Region;
use App\Repositories\UserAddressRepository;
use App\Models\UserAddress;
use Log;

class UserAddressService
{
    public function __construct(private UserAddressRepository $repo)
    {
    }

    public function getUserAddresses(int $userId)
    {
        $addresses = $this->repo->getAllByUserId($userId);

        return $addresses->map(function ($address) {
            return $this->transformAddress($address);
        });
    }

    public function createAddress(array $data, int $userId)
    {
        $data['user_id'] = $userId;
        $data['region'] = Helper::convert($data['region']);
        $find_region = Region::where('code', $data['region'])->first();
        $data['region_id'] =  $find_region->id;
        Log::info($data);
        $address = $this->repo->create($data);

        return $this->transformAddress($address);
    }

    public function updateAddress(int $addressId, array $data, int $userId)
    {
        $address = $this->repo->findByIdAndUserId($addressId, $userId);

        if (!$address) {
            return null;
        }

        $updatedAddress = $this->repo->update($address, $data);
        return $this->transformAddress($updatedAddress);
    }

    public function deleteAddress(int $addressId, int $userId)
    {
        $address = $this->repo->findByIdAndUserId($addressId, $userId);

        if (!$address) {
            return false;
        }

        return $this->repo->delete($address);
    }

    public function getAddressById(int $addressId, int $userId)
    {
        $address = $this->repo->findByIdAndUserId($addressId, $userId);

        if (!$address) {
            return null;
        }

        return $this->transformAddress($address);
    }

    public function getAddressesCount(int $userId)
    {
        return $this->repo->countByUserId($userId);
    }

    private function transformAddress(UserAddress $address)
    {
        return [
            'id' => $address->id,
            'title' => $address->title,
            'fname' => $address->fname,
            'lname' => $address->lname,
            'unit' => $address->unit,
            'number' => $address->number,
            'floor' => $address->floor,
            'full_name' => $address->fname . ' ' . $address->lname,
            'telephone' => $address->telephone,
            'mobile' => $address->mobile,
            'city' => $address->city,
            'region' => $address->region,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'address' => $address->address,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];
    }
}