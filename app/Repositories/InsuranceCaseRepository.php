<?php

namespace App\Repositories;

use App\Contracts\IInsuranceCase;
use App\Models\CarMake;
use App\Models\InsuranceCase;
use Illuminate\Support\Facades\Storage;

class InsuranceCaseRepository extends Repository implements IInsuranceCase
{
    public function model()
    {
        return app(InsuranceCase::class);
    }

    public function makesModel()
    {
        return app(CarMake::class);
    }

    /**
     * Get all data
     *
     * @param $request
     * @param $user_id
     * @return mixed
     */
    public function all($request = null, $user_id = null): mixed
    {
        $query = $this->model()
            ->with(['carMake', 'carModel']);

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        if ($request && $request->has('filter')) {
            $search = $request->input('filter');
            $caseSearch = strtolower($search);
            $query->whereRaw('LOWER(case)', 'like', "%$caseSearch%")
                ->orWhere('mileage', $search)
                ->orWhere('color', $search)
                ->orWhereHas('carMake', function ($q) use ($caseSearch) {
                    $q->where('name', 'like', "%$caseSearch%");
                })
                ->orWhereHas('carModel', function ($q) use ($caseSearch) {
                    $q->where('model_name', 'like', "%$caseSearch%");
                });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get insurance cases data
     *
     * @param $request
     * @param null $user_id
     * @return mixed
     */
    public function list($request, $user_id = null): mixed
    {
        return $this->all($request, $user_id);
    }

    /**
     * Find insurance case data
     *
     * @param $el
     * @return InsuranceCase
     */
    public function find($el): InsuranceCase
    {
        $el->car_make = $el->carMake()->first();
        $el->car_model = $el->carModel()->first();
        $el->picture_url = $el->picture_name
            ? asset('storage/' . config('services.site.picture_folder') . "/" . auth()->user()->id . '/' . $el->picture_name)
            : null;

        return $el;
    }

    /**
     * Save insurance case data
     *
     * @param $data
     * @param $insuranceCase
     * @return InsuranceCase
     */
    public function save($data, $insuranceCase): InsuranceCase
    {
        if (empty($data['bought_at'])) {
            $data['bought_at'] = date('Y-m-d');
        }

        if (!empty($data['picture_image']) && !empty($data['picture_name'])) {
            $this->uploadPicture($data['picture_image'], $data['picture_name']);
        }

        $insuranceCase->fill($data);

        $insuranceCase->user_id = auth()->user()->id;

        $insuranceCase->save();

        return $this->find($insuranceCase);
    }

    /**
     * Get cars list
     *
     * @return mixed
     */
    public function carsList(): mixed
    {
        return $this->makesModel()
            ->with(['carModels' => function ($q) {
                $q->orderBy('car_make_id')
                    ->orderBy('model_name');
            }])
            ->orderBy('id')
            ->get();
    }

    /**
     * Decode base64
     *
     * @param $base64File
     * @return bool|string
     */
    private function decodeAndGetFile($base64File): bool|string
    {
        $replace = substr($base64File, 0, strpos($base64File, ',') + 1);
        $fileStr = str_replace($replace, '', $base64File);
        $fileStr = str_replace(' ', '+', $fileStr);

        return base64_decode($fileStr);
    }

    /**
     * Upload picture
     *
     * @param $base64File
     * @param $fileName
     * @return void
     */
    private function uploadPicture($base64File, $fileName): void
    {
        $content = $this->decodeAndGetFile($base64File);

        $userID = auth()->user()->id;
        $path = 'public/' . config('services.site.picture_folder') . "/$userID/$fileName";
        Storage::disk('local')->put($path, $content);
    }
}
