<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;

use App\Models\Chart;

use App\Models\Order;

use Session;

use Stripe;



class HomeController extends Controller
{

    public function index()
    {
        $product = Product::paginate(10);
        return view('home.userpage',compact('product'));
    }
    public function redirect()
    {
        $usertype = Auth::user()->usertype;

        if($usertype == '1')
        {
            return view('admin.home');
        }

        else{

            $product = Product::paginate(10);
            return view('home.userpage',compact('product'));
        }
    }

    public function product_details($id)
    {
        $product=product::find($id);
        return view('home.product_details',compact('product'));
    }

    public function add_chart(Request $request,$id)
    {
        if(Auth::id())
        {
            $user=Auth::user();
            $product = product::find($id);
            $chart = new chart;

            $chart->name=$user->name;

            $chart->email=$user->email;

            $chart->phone=$user->phone;

            $chart->address=$user->address;

            $chart->user_id=$user->id;

            $chart->product_title=$product->title;

            if($product->discount_price!=null)
            {

                $chart->price=$product->discount_price * $request->quantity;

            }

            else
            {
                $chart->price=$product->price * $request->quantity;
            }



            $chart->image=$product->image;

            $chart->product_id=$product->id;

            $chart->quantity=$request->quantity;

            $chart->save();

            return redirect()->back();


        }

        else
        {
            return redirect('login');
        }
    }

    public function show_chart()
    {
        if(Auth::id())
        {
            $id=Auth::user()->id;
            $chart=chart::where('user_id','=',$id)->get();
            return view('home.showchart',compact('chart'));

        }

        else
        {
            return redirect('login');
        }

    }

    public function remove_chart($id)
    {
        $chart=chart::find($id);

        $chart->delete();

        return redirect()->back();

    }

    public function cash_order()
    {

        $user=Auth::user();

        $userid = $user->id;

        $data = chart::where('user_id','=',$userid)->get();

        foreach($data as $data)
        {
            $order=new order;

            $order->name=$data->name;

            $order->email=$data->email;

            $order->phone=$data->phone;

            $order->address=$data->address;

            $order->user_id=$data->user_id;

            $order->product_title=$data->product_title;

            $order->price=$data->price;

            $order->quantity=$data->quantity;

            $order->image=$data->image;

            $order->product_id=$data->product_id;

            $order->payment_status='cash on delivery';

            $order->delivery_status='processing';

            $order->save();

            $chart_id=$data->id;

            $chart=chart::find($chart_id);

            $chart->delete();
        }

        return redirect()->back()->with('message','we have Received Your Order.We wiil
        connect with you soon..');

    }

    public function stripe($totalprice)
    {
        return view('home.stripe',compact('totalprice'));
    }

    public function stripePost(Request $request,$totalprice)
    {

        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        Stripe\Charge::create ([
                "amount" => $totalprice * 100,
                "currency" => "usd",
                "source" => $request->stripeToken,
                "description" => "Thanks  for payment."
        ]);

        $user=Auth::user();

        $userid = $user->id;

        $data = chart::where('user_id','=',$userid)->get();

        foreach($data as $data)
        {
            $order=new order;

            $order->name=$data->name;

            $order->email=$data->email;

            $order->phone=$data->phone;

            $order->address=$data->address;

            $order->user_id=$data->user_id;

            $order->product_title=$data->product_title;

            $order->price=$data->price;

            $order->quantity=$data->quantity;

            $order->quantity=$data->quantity;

            $order->image=$data->image;

            $order->product_id=$data->product_id;

            $order->payment_status='Paid';

            $order->delivery_status='processing';

            $order->save();

            $chart_id=$data->id;

            $chart=chart::find($chart_id);

            $chart->delete();
        }

        Session::flash('success', 'Payment successful!');

        return back();
    }


}
