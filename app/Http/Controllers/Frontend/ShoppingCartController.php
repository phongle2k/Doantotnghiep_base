<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShoppingCartController extends Controller
{
    public function index()
    {
        $products = \Cart::content();
        $viewData = [
            'products' => $products
        ];
        return view('frontend.shopping.index', $viewData);
    }

    public function checkout()
    {
        $products = \Cart::content();
        $user     = User::find(get_data_user('web'));
        $viewData = [
            'user'     => $user,
            'products' => $products
        ];
        return view('frontend.shopping.checkout', $viewData);
    }

    public function update(Request $request)
    {
        $qty = $request->qty ?? [];
        $products = $request->products ?? [];
        foreach ($request->ids as  $key => $item) {
            try {
                $productID = $products[$key];
                $product = Product::find($productID);
                if($product && $product->pro_number >= $qty[$key])
                {
                    \Cart::update($item, $qty[$key]); // Will update the quantity
                }
            } catch (\Exception $exception) {

            }
        }

        return redirect()->back()->with('success','Cập nhật thành công');
    }

    public function pay(Request $request)
    {
        $dataTransaction                  = $request->except('_token');
        $dataTransaction['created_at']    = Carbon::now();
        $dataTransaction['t_user_id']     = get_data_user('web') ?? 0;
        $dataTransaction['t_note']        = 'Nhận hàng mới thanh toán';
        $dataTransaction['t_total_money'] = (int)str_replace(',', '', \Cart::subtotal(0));
        $transaction                      = Transaction::create($dataTransaction);
        if ($transaction) {
            $products = \Cart::content();
            foreach ($products as $item) {
                Order::create([
                    'od_transaction_id' => $transaction->id,
                    'od_product_id'     => $item->id,
                    'od_qty'            => $item->qty,
                    'od_price'          => $item->price,
                    'created_at'        => Carbon::now()
                ]);
            }
        }

        \Cart::destroy();
        return redirect()->route('get.shopping.success', ['transactionID' => $transaction->id]);
    }

    public function success(Request $request)
    {
        $transaction = Transaction::find($request->transactionID);
        if (!$transaction) return abort(404);

        return view('frontend.shopping.success', ['transaction' => $transaction]);
    }
}
