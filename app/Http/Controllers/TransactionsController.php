<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use App\Models\Transactions;
use Carbon\Carbon;
use App\Models\StockInReport;

class TransactionsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /*
    |--------------------------------------------------------------------------
    | Stock In
    |--------------------------------------------------------------------------
    */

    public function stock_in()
    {   
        $category = Information::where('information_type', 'CATEGORY')->orderBy('id', 'Desc')->get();
        $location = Information::where('information_type', 'LOCATION')->orderBy('id', 'Desc')->get();
        $suppliers = Information::where('information_type', 'SUPPLIERS')->orderBy('id', 'Desc')->get();
        $size = Information::where('information_type', 'SIZE')->orderBy('id', 'Desc')->get();


        return view('transactions.stockin.create', compact('category', 'location', 'suppliers', 'size'));
    }

    public function stock_in_list()
    {   
        $stock_in = Transactions::orderBy('id', 'Desc')->where('type_transaction', 'IN')->paginate(10);

        return view('transactions.stockin.list', compact('stock_in'));
    }

    public function stock_in_store(Request $request)
    {   
        $information = Information::where('information_id', $request->category)->first();
        $transaction = Transactions::orderBy('id', 'Desc')->first();

        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,HEIC|max:2048',
            'invoice' => 'mimes:pdf|max:2048',
        ]);

        $total = $transaction->id + 1;

        $tracking_id = $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . $total . '-' . 'IN';

        if ($request->invoice == "" && $request->image == "") {
            Transactions::create([
                'tracking_id' => $tracking_id,
                // 'tracking_id' => $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . 1 . '-' . 'IN',
                'name' => $request->name,
                'category_id' => $request->category,
                'location_id' => $request->location,
                'suppliers_id' => $request->suppliers,
                'size_id' => $request->size,
                'invoice_id' => 0,
                'image' => 0,
                'total_stock' => $request->total_stock,
                'minimum_stock' => $request->minimum_stock,
                'type_transaction' => 'IN',
                'purpose' => 0,
                'stock_in_id' => 0,
                'stock_status' => 'normal',
                'short_code' => $information->short_code,
            ]);
        }elseif ($request->invoice == "") {

            $pdfname = 'pdf_' . uniqid() . '.' . $request->invoice->extension();
            $request->invoice->move(public_path('pdf/invoices'), $pdfname);

            Transactions::create([
                'tracking_id' => $tracking_id,
                // 'tracking_id' => $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . 1 . '-' . 'IN',
                'name' => $request->name,
                'category_id' => $request->category,
                'location_id' => $request->location,
                'suppliers_id' => $request->suppliers,
                'size_id' => $request->size,
                'invoice_id' => 0,
                'image' => $imagename,
                'total_stock' => $request->total_stock,
                'minimum_stock' => $request->minimum_stock,
                'type_transaction' => 'IN',
                'purpose' => 0,
                'stock_in_id' => 0,
                'stock_status' => 'normal',
                'short_code' => $information->short_code,
            ]);
        }elseif ($request->image == "") {

            $imagename = 'img_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images/stockin'), $imagename);

            Transactions::create([
                'tracking_id' => $tracking_id,
                // 'tracking_id' => $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . 1 . '-' . 'IN',
                'name' => $request->name,
                'category_id' => $request->category,
                'location_id' => $request->location,
                'suppliers_id' => $request->suppliers,
                'size_id' => $request->size,
                'invoice_id' => $pdfname,
                'image' => 0,
                'total_stock' => $request->total_stock,
                'minimum_stock' => $request->minimum_stock,
                'type_transaction' => 'IN',
                'purpose' => 0,
                'stock_in_id' => 0,
                'stock_status' => 'normal',
                'short_code' => $information->short_code,
            ]);
        }else{

            $imagename = 'img_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images/stockin'), $imagename);

            $pdfname = 'pdf_' . uniqid() . '.' . $request->invoice->extension();
            $request->invoice->move(public_path('pdf/invoices'), $pdfname);

            Transactions::create([
                'tracking_id' => $tracking_id,
                // 'tracking_id' => $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . 1 . '-' . 'IN',
                'name' => $request->name,
                'category_id' => $request->category,
                'location_id' => $request->location,
                'suppliers_id' => $request->suppliers,
                'size_id' => $request->size,
                'invoice_id' => $pdfname,
                'image' => $imagename,
                'total_stock' => $request->total_stock,
                'minimum_stock' => $request->minimum_stock,
                'type_transaction' => 'IN',
                'purpose' => 0,
                'stock_in_id' => 0,
                'stock_status' => 'normal',
                'short_code' => $information->short_code,
            ]);
        }

        StockInReport::create([
            'total_stock' => $request->total_stock,
            'tracking_id' => $tracking_id,
        ]);


        return redirect()->back()->with('success', 'Stock was check in');
    }

    public function stock_in_update(Request $request,$id)
    {
        $stock_in = Transactions::where('tracking_id', $id)->first();

        $stock_in->total_stock = $request->total_stock + $stock_in->total_stock;
        $stock_in->notes = $request->notes;

        $stock_in->save();

        StockInReport::create([
            'total_stock' => $request->total_stock,
            'tracking_id' => $id,
        ]);

        return redirect()->back()->with('success', 'Stock was updated');
    }

    public function stock_in_search(Request $request)
    {
        $stock_in = Transactions::where('tracking_id', 'LIKE' , '%' . $request->track . '%')->orWhere('name', 'LIKE' , '%' . $request->track . '%')->where('type_transaction', 'IN')->paginate(10);

        if(count($stock_in) > 0)
        {
            return view('transactions.stockin.list', compact('stock_in'));

        }else{

            return redirect()->back()->with('error', 'Not found any stock.');
        }

    }

    public function stock_in_report()
    {
        $transaction_in = Transactions::orderBy('id', 'Desc')->where('type_transaction', 'IN')->get();
        $stock_in_report = StockInReport::orderBy('id', 'Desc')->get();

        return view('transactions.stockin.report', compact('stock_in_report', 'transaction_in'));
    }

    public function stock_in_filter_date(Request $request)
    {
        $stock_in = Transactions::where('type_transaction', 'IN')->orderBy('id', 'Desc')->whereBetween('updated_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])->get();

        return view('transactions.stockin.list', compact('stock_in'));
    }

    public function stock_in_edit($id)
    {   
        $transaction = Transactions::where('tracking_id', $id)->first();

        $category = Information::where('information_type', 'CATEGORY')->where('information_id',$transaction->category_id)->first();
        $get_category = Information::where('information_type', 'CATEGORY')->where('information_id', '!=',$transaction->category_id)->orderBy('id', 'Desc')->get();

        $location = Information::where('information_type', 'LOCATION')->where('information_id',$transaction->location_id)->first();
        $get_location = Information::where('information_type', 'LOCATION')->where('information_id', '!=',$transaction->location_id)->orderBy('id', 'Desc')->get();

        $suppliers = Information::where('information_type', 'SUPPLIERS')->where('information_id',$transaction->suppliers_id)->first();
        $get_suppliers = Information::where('information_type', 'SUPPLIERS')->where('information_id', '!=',$transaction->suppliers_id)->orderBy('id', 'Desc')->get();

        $size = Information::where('information_type', 'SIZE')->where('information_id',$transaction->size_id)->first();
        $get_size = Information::where('information_type', 'SIZE')->where('information_id', '!=',$transaction->size_id)->orderBy('id', 'Desc')->get();

        return view('transactions.stockin.edit', compact('get_category', 'category', 'location', 'get_location', 'suppliers', 'get_suppliers', 'size', 'get_size', 'transaction'));
    }

    public function stock_in_update_info(Request $request,$id)
    {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,HEIC|max:2048',
            'invoice' => 'mimes:pdf|max:2048',
        ]);

        $transaction = Transactions::where('tracking_id', $id)->first();

        if ($request->image == "" && $request->invoice == "") {

            $transaction->name = $request->name;
            $transaction->category_id = $request->category;
            $transaction->location_id = $request->location;
            $transaction->suppliers_id = $request->suppliers;
            $transaction->size_id = $request->size;
            $transaction->total_stock = $request->total_stock;
            $transaction->minimum_stock = $request->minimum_stock;

            $transaction->save();

            return redirect()->back()->with('success', 'Stock Information Updated!');

        }elseif ($request->image == "") {

            $pdfname = 'pdf_' . uniqid() . '.' . $request->invoice->extension();
            $request->invoice->move(public_path('pdf/invoices'), $pdfname);

            $transaction->name = $request->name;
            $transaction->category_id = $request->category;
            $transaction->location_id = $request->location;
            $transaction->suppliers_id = $request->suppliers;
            $transaction->size_id = $request->size;
            $transaction->total_stock = $request->total_stock;
            $transaction->minimum_stock = $request->minimum_stock;
            $transaction->invoice_id = $pdfname;

            $transaction->save();

            return redirect()->back()->with('success', 'Stock Information Updated!');

        }elseif ($request->invoice == "") {

            $pdfname = 'pdf_' . uniqid() . '.' . $request->invoice->extension();
            $request->invoice->move(public_path('pdf/invoices'), $pdfname);

            $transaction->name = $request->name;
            $transaction->category_id = $request->category;
            $transaction->location_id = $request->location;
            $transaction->suppliers_id = $request->suppliers;
            $transaction->size_id = $request->size;
            $transaction->total_stock = $request->total_stock;
            $transaction->minimum_stock = $request->minimum_stock;
            $transaction->image = $imagename;

            $transaction->save();

            return redirect()->back()->with('success', 'Stock Information Updated!');

        }else {

            $imagename = 'img_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images/stockin'), $imagename);

            $pdfname = 'pdf_' . uniqid() . '.' . $request->invoice->extension();
            $request->invoice->move(public_path('pdf/invoices'), $pdfname);

            $transaction->name = $request->name;
            $transaction->category_id = $request->category;
            $transaction->location_id = $request->location;
            $transaction->suppliers_id = $request->suppliers;
            $transaction->size_id = $request->size;
            $transaction->total_stock = $request->total_stock;
            $transaction->minimum_stock = $request->minimum_stock;
            $transaction->invoice_id = $pdfname;
            $transaction->image = $imagename;

            $transaction->save();

            return redirect()->back()->with('success', 'Stock Information Updated!');

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Out
    |--------------------------------------------------------------------------
    */

    public function stock_out()
    {
        $stock_in = Transactions::where('type_transaction', 'IN')->paginate(10);

        return view('transactions.stockout.create', compact('stock_in'));
    }

    public function stock_out_list()
    {   
        $stock_out = Transactions::where('type_transaction', 'OUT')->paginate(10);

        return view('transactions.stockout.list', compact('stock_out'));
    }

    public function stock_out_create(Request $request, $id)
    {
        $stock_out = Transactions::where('tracking_id', $id)->first();

        if($request->total_stock >= $stock_out->total_stock)
        {

            return redirect()->back()->with('error', 'Invalid amount, please try again.');

        }else {

            $transaction = Transactions::orderBy('id', 'Desc')->first();
            $total = $transaction->id + 1;

            Transactions::create([
                'tracking_id' => $stock_out->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . $total . '-' . 'OUT',
                'name' => $stock_out->name,
                'category_id' => $stock_out->category_id,
                'location_id' => $stock_out->location_id,
                'suppliers_id' => $stock_out->suppliers_id,
                'size_id' => $stock_out->size_id,
                'invoice_id' => 0,
                'image' => 0,
                'total_stock' => $request->total_stock,
                'minimum_stock' => 0,
                'type_transaction' => 'OUT',
                'purpose' => $request->purpose,
                'stock_in_id' => $stock_out->tracking_id,
                'stock_status' => 'normal',
                'short_code' => $stock_out->short_code,
                'stock_out_status' => $request->status,
            ]);


            $stock_out->total_stock = $stock_out->total_stock - $request->total_stock;

            if($stock_out->total_stock <= $stock_out->minimum_stock)
            {
                $stock_out->stock_status = 'low';
                $stock_out->save();

            }else{
                
                $stock_out->save();

            }

            return redirect()->back()->with('success', 'Stock quantity updated.');

        }
    }

    public function stock_out_search(Request $request)
    {
        $stock_in = Transactions::where('tracking_id', 'LIKE' , '%' . $request->track . '%')->orWhere('name', 'LIKE' , '%' . $request->track . '%')->where('type_transaction', 'IN')->paginate(10);

        if(count($stock_in) > 0)
        {
            return view('transactions.stockout.create', compact('stock_in'));

        }else{

            return redirect()->back()->with('error', 'Not found any stock.');
        }

    }

    public function stock_out_list_search(Request $request)
    {
        $stock_out = Transactions::where('tracking_id', 'LIKE' , '%' . $request->track . '%')->orWhere('name', 'LIKE' , '%' . $request->track . '%')->where('type_transaction', 'OUT')->paginate(10);

        if(count($stock_out) > 0)
        {
            return view('transactions.stockout.list', compact('stock_out'));

        }else{

            return redirect()->back()->with('error', 'Not found any stock.');
        }

    }

    public function stock_out_filter_date(Request $request)
    {
        $stock_out = Transactions::where('type_transaction', 'OUT')->orderBy('id', 'Desc')->whereBetween('updated_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])->get();

        return view('transactions.stockout.list', compact('stock_out'));
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Return
    |--------------------------------------------------------------------------
    */

    public function stock_return()
    {
        $category = Information::where('information_type', 'CATEGORY')->orderBy('id', 'Desc')->get();
        $location = Information::where('information_type', 'LOCATION')->orderBy('id', 'Desc')->get();
        $suppliers = Information::where('information_type', 'SUPPLIERS')->orderBy('id', 'Desc')->get();
        $size = Information::where('information_type', 'SIZE')->orderBy('id', 'Desc')->get();

        return view('transactions.stockreturn.create', compact('category', 'location', 'suppliers', 'size'));
    }

    public function stock_return_store(Request $request)
    {
        $information = Information::where('information_id', $request->category)->first();
        $transaction = Transactions::orderBy('id', 'Desc')->first();
        $total = $transaction->id + 1;

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagename = 'img_' . uniqid() . '.' . $request->image->extension();
        $request->image->move(public_path('images/stockreturn'), $imagename);


        Transactions::create([
            'tracking_id' => $information->short_code . '-' . date('Y') . '-' . date('m') . '-' . date('d') . '-' . $total . '-' . 'RETURN',
            'name' => $request->name,
            'category_id' => $request->category,
            'location_id' => 0,
            'suppliers_id' => 0,
            'size_id' => $request->size,
            'invoice_id' => 0,
            'image' => $imagename,
            'total_stock' => $request->total_stock,
            'minimum_stock' => 0,
            'type_transaction' => 'RETURN',
            'purpose' => $request->purpose,
            'stock_in_id' => 0,
            'stock_status' => 'normal',
            'short_code' => $information->short_code,
        ]);

        return redirect()->back()->with('success', 'Stock return created.');

    }

    public function stock_return_list()
    {   
        $stock_return = Transactions::where('type_transaction', 'RETURN')->paginate(10);

        return view('transactions.stockreturn.list', compact('stock_return'));
    }

    public function stock_return_search(Request $request)
    {
        $stock_return = Transactions::where('tracking_id', 'LIKE' , '%' . $request->track . '%')->orWhere('name', 'LIKE' , '%' . $request->track . '%')->where('type_transaction', 'RETURN')->paginate(10);

        if(count($stock_return) > 0)
        {
            return view('transactions.stockreturn.list', compact('stock_return'));

        }else{

            return redirect()->back()->with('error', 'Not found any stock.');
        }

    }

    public function stock_return_filter_date(Request $request)
    {
        $stock_return = Transactions::where('type_transaction', 'RETURN')->orderBy('id', 'Desc')->whereBetween('updated_at', [$request->from . " 00:00:00", $request->to . " 23:59:59"])->get();

        return view('transactions.stockreturn.list', compact('stock_return'));
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Reject
    |--------------------------------------------------------------------------
    */

    public function stock_reject()
    {
        $category = Information::where('information_type', 'CATEGORY')->orderBy('id', 'Desc')->get();
        $location = Information::where('information_type', 'LOCATION')->orderBy('id', 'Desc')->get();
        $suppliers = Information::where('information_type', 'SUPPLIERS')->orderBy('id', 'Desc')->get();
        $size = Information::where('information_type', 'SIZE')->orderBy('id', 'Desc')->get();

        return view('transactions.stockreject.create', compact('category', 'location', 'suppliers', 'size'));
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $information = Transactions::where('tracking_id', $id);

        $information->delete();

        return redirect()->back()->with('success', 'Remove successfully.');
    }
}
