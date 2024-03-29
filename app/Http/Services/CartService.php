<?php


namespace App\Http\Services;

use Illuminate\Support\Facades\Mail;
use App\Jobs\SendMail;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function create($request)
    {
        $qty = (int)$request->input('num_product');
        $product_id = (int)$request->input('product_id');
        if ($qty <= 0 || $product_id <= 0) {
            Session::flash('error', 'Số lượng hoặc Sản phẩm không chính xác');
            return false;
        } elseif (!Auth::check()) {
            Session::flash('error', 'Cần đăng nhập để mua hàng');
            return false;
        }


        $carts = Session::get('carts');
        if (is_null($carts)) {
            Session::put('carts', [
                $product_id => $qty
            ]);
            return true;
        }
        //neu ton tai thi update
        $exists = Arr::exists($carts, $product_id);
        if ($exists) {
            $carts[$product_id] = $carts[$product_id] + $qty;
            Session::put('carts', $carts);
            return true;
        }


        $carts[$product_id] = $qty;
        Session::put('carts', $carts);

        return true;
    }

    public function getProduct()
    {
        $carts = Session::get('carts');
        if (is_null($carts)) return [];

        $productId = array_keys($carts);
        return Product::select('id', 'name', 'price', 'price_sale', 'thumb')
            ->where('active', 1)
            ->whereIn('id', $productId)
            ->get();
    }



    public function update($request)
    {
        $cartData = $request->input('num_product');

        // Loop through the cart data and check each quantity
        foreach ($cartData as $productId => $quantity) {
            $quantity = (int)$quantity;

            if ($quantity <= 0) {
                Session::flash('error', 'số lượng phải lớn hơn 0');
                $cartData[$productId] = 1;
                return false;
            }
        }

        Session::put('carts', $cartData);
        Session::flash('success', 'Cập nhật số lượng và sản phẩm thành công');

        return true;
    }


    public function remove($id)
    {
        $carts = Session::get('carts');
        unset($carts[$id]);

        Session::put('carts', $carts);
        return true;
    }

    public function addCart($request)
    {
        if (Session::get('carts')) {
            DB::beginTransaction();

            $carts = Session::get('carts');
            if (is_null($carts)) {
                return false;
            }
            // dd($request->input());
            $validatedData = $request->validate([
                'name' => 'required|max:255',
                'phone' => 'required|max:10',
                'address' => 'required|max:255',
                'email' => 'required|email|max:255',
                'content' => 'nullable|max:500',
                'user_id' => 'required|exists:users,id',
            ]);
            
            $customer = Customer::create([
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address'],
                'email' => $validatedData['email'],
                'content' => $validatedData['content'],
                'user_id' => $validatedData['user_id']
            ]);



            $this->infoProductCart($carts, $customer->id);


            DB::commit();
            Session::flash('success', 'Đặt Hàng Thành Công');
            // $email = $request->input('email');
            // $name = $request->input('name');
            // $phone = $request->input('phone');
            // $content = $request->input('content');

            // SendMail::dispatch($email, $name, $phone, $content)->delay(now()->addSeconds(2));

            Session::forget('carts');
            return true;
        } else {
            DB::rollBack();
            Session::flash('error', 'Đặt Hàng Lỗi, Vui lòng thử lại sau');
            return false;
        }
    }

    protected function infoProductCart($carts, $customer_id)
    {
        $productId = array_keys($carts);
        $products = Product::select('id', 'name', 'price', 'price_sale', 'thumb')
            ->where('active', 1)
            ->whereIn('id', $productId)
            ->get();

        // Create the order with a unique order number
        $order = new Order();
        $order->customer_id = $customer_id;
        $order->order_number = $this->generateUniqueOrderNumber(); // Generate a unique order number
        $order->total_amount = 0; // You can calculate this later
        $order->status = 'pending';
        $order->order_date = now();
        $order->save();

        $totalAmount = 0;


        // Create and store multiple order items (Cart) without removing them from the cart
        foreach ($products as $product) {
            $price = $product->price_sale != 0 ? $product->price_sale : $product->price;
            $qty = $carts[$product->id];

            // Calculate total amount
            $totalAmount += $price * $qty;

            // Create an order item (Cart) and store it
            $order_items = new OrderItem();
            $order_items->order_id = $order->id;
            $order_items->product_id = $product->id;
            $order_items->quantity = $qty; // Use 'quantity' instead of 'qty' to match your table column name
            $order_items->price = $price;
            $order_items->save();
        }

        // Update the total amount in the order
        $order->total_amount = $totalAmount;
        $order->save();

        return $order; // Return the created order
    }


    protected function generateUniqueOrderNumber()
    {
        // Generate a unique order number based on your requirements
        // For example, you can use a combination of a prefix and an auto-incremented value
        $prefix = 'ORD-'; // You can customize the prefix
        $orderNumber = $prefix . (Order::max('id') + 1);
        return $orderNumber;
    }



 
    protected function calculateTotalAmount($carts)
    {
        $totalAmount = 0;

        if (is_array($carts) && count($carts) > 0) {
            $productIds = array_keys($carts);

            $products = Product::whereIn('id', $productIds)->get();

            foreach ($products as $product) {
                $price = $product->price_sale != 0 ? $product->price_sale : $product->price;
                $totalAmount += $price * $carts[$product->id];
            }
        }

        return $totalAmount;
    }

    public function getProductForCart($customer)
    {
        return $customer->carts()->with(['product' => function ($query) {
            $query->select('id', 'name', 'thumb');
        }])->get();
    }
}
