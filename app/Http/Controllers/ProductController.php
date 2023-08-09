<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
//        return response()->json(url('storage/images/4enrnmBvvn.png'));
        $page = $request->get('page') == null ? 1 : (int)$request->get('page');
        $perpage = $request->get('perpage') == null ? 10 : (int)$request->get('perpage');
        $where = $request->get('name') == null ? '' : $request->get('name');
        $data = DB::table('product')->where('name', 'like', '%' . $where . '%')->orderBy('id', 'DESC')->paginate($perpage);
        $total = DB::table('product')->where('name', 'like', '%' . $where . '%')->orderBy('id', 'DESC')->get();
        $datas = [];
        $response = [];
        if ($data != null) {
            return response()->json($data, Response::HTTP_OK);
        } else {
            return response()->json(['status' => false, 'data' => [], 'pagination' => []], Response::HTTP_OK);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->coreField($request, null);
            if (gettype($data) == 'object') {
                DB::rollback();
                return $data;
            }
            DB::table('product')->insert($data);
            DB::commit();
            return response()->json(['message' => $data], 200);
        } catch (QueryException $e) {
            DB::rollback();
            return response()->json($e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $this->coreField($request, $id);
            if (gettype($data) == 'object') {
                DB::rollback();
                return $data;
            }
            DB::table('product')->where('id', $id)->update($data);
            DB::commit();
            return response()->json(['message' => $data], 200);
        } catch (QueryException $e) {
            DB::rollback();
            return response()->json($e);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        $resDb = DB::table('product')->where('id', $id)->first();
        try {
            $message = '';
            if (Storage::disk('public')->exists($resDb->image)) {
                $message = 'hapus gambar di folder';
                Storage::disk('public')->delete($resDb->image);
            }
            DB::table('product')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['message' => $message], 200);
        } catch (QueryException $e) {
            DB::rollback();
            return response()->json(['message' => false], 400);
        }
    }


    public function coreField(Request $request, $id)
    {
        $isValidate = [
            'name' => 'required',
//            'description' => 'required',
            'price' => 'required',
        ];
        $data = [
            'name' => $request->post('name'),
            'description' => '',
            'price' => $request->post('price'),
        ];
        if ($id != null) {
            $resDb = DB::table('product')->where('id', $id)->first();
//            $isValidate['code'] = 'unique:province,code,' . $id;
        } else {
            $data['image'] = '';
//            $isValidate['code'] = 'required|unique:province';
        }

        $validator = Validator::make($request->all(), $isValidate);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 200);
        }

        $image_64 = $request->post('image');


        if ($image_64 != '' || $image_64 != null) {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);
            $image = str_replace($replace, '', $image_64);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10) . '.' . $extension;
            if (Storage::disk('public')->exists($id != null ? $resDb->image : $imageName)) {
                Storage::disk('public')->delete($id != null ? $resDb->image : $imageName);
            }
            $file = Storage::disk('public')->put($imageName, base64_decode($image));
            $data['image'] = $imageName;
        }
        return $data;
    }
}
