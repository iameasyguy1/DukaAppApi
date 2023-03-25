<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ApiHelpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {

        if ($request->user()->role===1) {
            $orders = DB::table('orders')->get();
            return $this->onSuccess($orders, 'Orders Retrieved');
        }elseif($request->user()->role===2){
            $orders =$request->user()->orders;
            return $this->onSuccess($orders, 'Orders Retrieved');
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
        $rules =[
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'notes' => 'string',
            'mpesa_no' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:10',
            'shipping_address' => 'string',
            'order_status' => 'string|max:255',
            'payment_status' => 'string|max:255',
            'tranx_ref' => 'string|max:255',
            'page_id' => 'required|exists:pages,id',
        ];
        //run validation
        // Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }
        if(!get_number_type($request->phone)){
            return response()->json([
                'success' => false,
                'errors' => "Invalid Phone number"
            ], 422);
        }
        if(!get_number_type($request->mpesa_no)){
            return response()->json([
                'success' => false,
                'errors' => "Invalid Mpesa Phone number"
            ], 422);
        }
        Log::info($request->all());

        $order = Order::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'mpesa_no' => $request->mpesa_no,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'shipping_address' => $request->shipping_address,
            'order_status' => $request->order_status,
            'payment_status' => $request->payment_status,
            'tranx_ref' => $request->tranx_ref,
            'page_id' => $request->page_id,
        ]);
        return $this->onSuccess($order, 'Order Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        if ($request->user()->role===1) {
            $order=Order::find($id);
        }else{
            $order = $request->user()->pages()->orders()->find($id);
        }
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        return $this->onSuccess($order, 'Order retrieved successfully');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request$request, $id)
    {
        if ($request->user()->role===1) {
            $page=Order::find($id);
        }else{
            $page = $request->user()->pages()->orders()->find($id);
        }
        if (!$page) {
            return $this->onError(404,'Order not found');
        }
        $page->delete();
        return $this->onSuccess($page, 'Order deleted successfully');

    }

}
