<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagesRequest;
use App\Models\Image;
use App\Models\Page;
use App\Traits\ApiHelpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;
class PageController extends Controller
{
    use ApiHelpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        //
        if ($request->user()->role===1) {
            $pages = DB::table('pages')->get();
            return $this->onSuccess($pages, 'Pages Retrieved');
        }elseif($request->user()->role===2){
            $pages = $request->user()->pages;
            return $this->onSuccess($pages, 'Pages Retrieved');
        }else{
            return $this->onError(401, 'An error occurred');
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'unique:pages'],
            'price' => ['required', 'integer', 'min:10'],
            'sale_price' => ['required', 'integer', 'min:10'],
            'min_purchase' => ['required','integer', 'min:1'],
            'max_purchase' => ['required','integer', 'min:1'],
            'images.*' => 'required|string',

        ];
        Log::info($request->all());
        // Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);
// If validation fails, return a JSON error response
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }

        Log::info($request->input('images[]'));

        $page =$request->user()->pages()->create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'min_purchase' => $request->min_purchase,
            'max_purchase' => $request->max_purchase,
        ]);

//       $page->addMediaFromUrl($request->images)
//           ->each(function ($fileAdder) {
//               $fileAdder->toMediaCollection('public/pages/images');
//           });
        foreach ($request->input('images[]') as $url){
            $media = $page->images()->create(['url'=>$url]);
        }
       Log::warning($page);
        return response()->json([
        'status' => 200,
        'message' => 'Page generated successfully',
        'data' => $page,

    ], 200);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $slug)
    {

            $page=Page::where('slug', $slug)->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
        $mediaItems = $page->images;

        $pageDetails = [
            'id' => $page->id,
            'name' => $page->name,
            'description' => $page->description,
            'slug' => $page->slug,
            'price' => $page->price,
            'sale_price' => $page->sale_price,
            'min_purchase' => $page->min_purchase,
            'max_purchase' => $page->max_purchase,
            'images' => $mediaItems,
            'owner'=>[
                'name' => $page->user->name,
                'image' => $page->user->image,

            ]
        ];

        return $this->onSuccess($pageDetails, 'Page retrieved successfully');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Log::info($request->user());
        Log::info($request->all());
        if ($request->user()->role===1) {
            $page=Page::find($id);
        }else{
            $page = $request->user()->pages()->find($id);
        }
        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'unique:pages,slug,' . $id],
            'price' => ['required', 'integer', 'min:10'],
            'sale_price' => ['required', 'integer', 'min:10'],
            'min_purchase' => ['required','integer', 'min:1'],
            'max_purchase' => ['required','integer', 'min:1'],
            'images.*' => 'string',
        ];

        // Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);

        // If validation fails, return a JSON error response
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $page->update([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'min_purchase' => $request->min_purchase,
            'max_purchase' => $request->max_purchase,
        ]);

        if($request->has('images[]')){
            DB::table('images')->where('page_id', '=', $page->id)->delete();
            foreach ($request->input('images[]') as $url){

            $page->images()->create(['url'=>$url]);

            }


        }

        $mediaItems = $page->images;

        $pageDetails = [
            'id' => $page->id,
            'name' => $page->name,
            'description' => $page->description,
            'slug' => $page->slug,
            'price' => $page->price,
            'sale_price' => $page->sale_price,
            'min_purchase' => $page->min_purchase,
            'max_purchase' => $page->max_purchase,
            'images' => $mediaItems,
            'owner'=>[
                'name' => $page->user->name,
                'image' => $page->user->image,

            ]
        ];
        return $this->onSuccess($pageDetails, 'Page updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request$request, $id)
    {
        if ($request->user()->role===1) {
            $page=Page::find($id);
        }else{
            $page = $request->user()->pages()->find($id);
        }
        if (!$page) {
            return $this->onError(404,'Page not found');
        }
        $page->delete();
       return $this->onSuccess($page, 'Page deleted successfully');

    }

    public function search(Request $request,$query){

        $page=Page::where('slug','LIKE', '%' . $query . '%')->get();
        if(count($page)>0){
            return $this->onSuccess($page, 'Page found!');
        }else{
            return $this->onError(404, 'Page not found!');

        }




    }

}
