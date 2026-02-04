<?php

namespace App\Http\Controllers;

use Hashids\Hashids;
use Stancl\Tenancy\Tenancy;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Setting\Models\Setting;
use Modules\DressCategory\Models\DressCategory;
use Modules\Booking\Models\BookingDress;

class DashboardController extends Controller
{
    /**
     * Show the dashboard view.
     */
    public function index()
    {
        $users = User::paginate(10);
        return view('welcome',compact('users')); 
        // or return response()->json(['message' => 'Welcome to the dashboard']);
    }
    public function bookingBill($tenant_id, $hash)
    {
        try {
            // 1) Initialize tenant manually
            tenancy()->initialize($tenant_id);

            // 2) Decode booking id 
            $hashids = new Hashids(env('APP_KEY'), 10);
            $id = $hashids->decode($hash)[0] ?? null;

            if (!$id) {
                abort(404);
            }

            $bookings = Booking::with(
                'payments',
                'customer',
                'finePayments',
                'fines',
                'bill',
                'dresses.dress',
                'dresses.accessories',
                'dresses.alterations', 
            )
            ->find($id);  
            if (!$bookings) {
                abort(404, 'Booking not found');
            }

            $setting = Setting::first();

            $table_data="";
            $terms="";
            
            
            $sno = 1;   // global continuous serial number
            $dress_status = $bookings->dresses->first()->status ?? null;
            foreach ($bookings->dresses as $item)
            {   
                /** ----------------------------------
                 *  ACCESSORIES (TYPE 1)
                 * ----------------------------------*/
                $accList = "";
                $accessories = $item->accessories->where('type', 1);

                foreach ($accessories as $acc) {
                    $accList .= "• " . $acc->accessory_title . "<br>";
                }

                /** ----------------------------------
                 *  ALTERATIONS
                 * ----------------------------------*/
                $alt_list = "";
                foreach ($item->alterations as $alt) {
                    $alt_list .= "• " . $alt->alteration.' ('.$alt->price.' ر.ع)'."<br>";
                }

                 

                /** ----------------------------------
                 *  FIRST PRINT THE MAIN DRESS ROW
                 * ----------------------------------*/
                $table_data .= '
                    <tr class="border-t">
                        <td class="p-3">'.$sno.'</td>
                        <td class="p-3 text-right leading-6">
                            <div class="font-bold text-center">'.$item->dress->dress_code.'</div>
                            <div class="text-xs text-gray-700 mt-2">
                                <span class="font-semibold text-gray-900">'.$item->dress->title.'</span><br>
                                '.$accList.'
                            </div>
                        </td>

                        <td class="p-3">'.$item->price.' ر.ع</td> 
                        <td class="p-3">'.number_format($item->price-$item->discount_amount,3).' ر.ع</td> 
                        <td class="p-3">إيجار</td>

                        <td class="p-3 text-right text-xs leading-5">'.$alt_list.'</td>
                    </tr>';

                $sno++;  // increase serial after dress row


                /** ----------------------------------
                 *  PRINT EXTRA ACCESSORIES (TYPE 2)
                 * ----------------------------------*/
                $extra_accessories = $item->accessories->where('type', 2);

                foreach ($extra_accessories as $ex_acc) {  
                    $table_data .= '
                    <tr class="border-t bg-gray-50">
                        <td class="p-3">'.$sno.'</td>
                        <td class="p-3 text-right">
                            <span class="text-xs text-gray-600">'.$ex_acc->accessory_title.'<br>
                                (ملحق إضافي للفستان '.$item->dress->dress_code.')
                            </span>
                        </td>
                        <td class="p-3">'.$ex_acc->price.' ر.ع</td> 
                        <td class="p-3">'.$ex_acc->price.' ر.ع</td> 
                        <td class="p-3">إكسسوار خارجي</td>
                        <td class="p-3 text-xs">بدون</td>
                    </tr>';

                    $sno++; // increase serial AFTER EACH EXTRA ACCESSORY
                }


                /** ----------------------------------
                 *  TERMS & CONDITIONS
                 * ----------------------------------*/
                $category = DressCategory::find($item->dress->dress_category_id);

                if ($category) {
                    $terms .= '
                        <h3 class="font-bold mb-2">شروط '.$category->title.'  :</h3>
                        <p class="text-xs text-gray-600 space-y-2 leading-5" style="white-space:pre-line">'
                            .$category->rules.
                        '</p>';
                }
            }

            // get paid or unpaid status for amount of security
             
            $total_deposit = getPaymentTotal('booking_payments','booking_id',$id,2);
            $total_fine1 = getFineTotal('booking_dress_fine_payments','booking_id',$id,1);
            $total_fine2 = getFineTotal('booking_dress_fine_payments','booking_id',$id,2);
            $final_refund_amount = $total_deposit -$total_fine1-$total_fine2;
            $refund_flag=1;
            if($final_refund_amount >0)
            {
              $refund_flag =2;
            } 
            $deposit_status=""; 
            if($dress_status==2)
            {
                $deposit_status = "(مدفوع)";
            }
            else  if($dress_status==1)
            {
                $deposit_status = "(غير مدفوع)";
            }
            else
            {
                if($refund_flag==1)
                {
                    $deposit_status = "(المبلغ المسترجع)";
                }
                else
                {
                    $deposit_status = "(غير مدفوع)";
                }
            }
            


             
            return view('booking::booking_bill', compact('deposit_status','bookings','table_data','terms','setting'));
        
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // delviery bill
    // booking devliery bill
    public function bookingDeliveryBill($tenant_id, $hash)
    {
        try {
            // 1) Initialize tenant manually
            tenancy()->initialize($tenant_id);

            // 2) Decode booking id 
            $hashids = new Hashids(env('APP_KEY'), 10);
            $id = $hashids->decode($hash)[0] ?? null;

            if (!$id) {
                abort(404);
            } 
            $booking_dress = BookingDress::where('id',$id)->get();
            $booking_dress_data =BookingDress::find($id);

            $bookings = Booking::with(
                'payments',
                'customer',
                'finePayments',
                'fines',
                'bill',
                'dresses.dress',
                'dresses.accessories',
                'dresses.alterations', 
            )
            ->find($booking_dress_data->booking_id);  
            $setting = Setting::first();

            $table_data="";
            $terms="";
            $total_amount=0;
            $total_discount=0;
            $deposit_amount=0;
            $sno = 1;   // global continuous serial number

            foreach ($booking_dress as $item)
            {   
                /** ----------------------------------
                 *  ACCESSORIES (TYPE 1)
                 * ----------------------------------*/
                $accList = "";
                $accessories = $item->accessories->where('type', 1);

                foreach ($accessories as $acc) {
                    $accList .= "• " . $acc->accessory_title . "<br>";
                }

                /** ----------------------------------
                 *  ALTERATIONS
                 * ----------------------------------*/
                $alt_list = "";
                foreach ($item->alterations as $alt) {
                    $total_amount+=$alt->price;
                    $alt_list .= "• " . $alt->alteration.' ('.$alt->price.' ر.ع)'."<br>";
                }

                /** ----------------------------------
                 *  FIRST PRINT THE MAIN DRESS ROW
                 * ----------------------------------*/
                $table_data .= '
                    <tr class="border-t">
                        <td class="p-3">'.$sno.'</td>
                        <td class="p-3 text-right leading-6">
                            <div class="font-bold text-center">'.$item->dress->dress_code.'</div>
                            <div class="text-xs text-gray-700 mt-2">
                                <span class="font-semibold text-gray-900">'.$item->dress->title.'</span><br>
                                '.$accList.'
                            </div>
                        </td>

                        <td class="p-3">'.$item->price.' ر.ع</td> 
                        <td class="p-3">'.number_format($item->price-$item->discount_amount,3).' ر.ع</td> 
                        <td class="p-3">إيجار</td>

                        <td class="p-3 text-right text-xs leading-5">'.$alt_list.'</td>
                    </tr>';

                $sno++;  // increase serial after dress row


                /** ----------------------------------
                 *  PRINT EXTRA ACCESSORIES (TYPE 2)
                 * ----------------------------------*/
                $extra_accessories = $item->accessories->where('type', 2);

                foreach ($extra_accessories as $ex_acc) {  
                    $table_data .= '
                    <tr class="border-t bg-gray-50">
                        <td class="p-3">'.$sno.'</td>
                        <td class="p-3 text-right">
                            <span class="text-xs text-gray-600">'.$ex_acc->accessory_title.'<br>
                                (ملحق إضافي للفستان '.$item->dress->dress_code.')
                            </span>
                        </td>
                        <td class="p-3">'.$ex_acc->price.' ر.ع</td> 
                        <td class="p-3">'.$ex_acc->price.' ر.ع</td> 
                        <td class="p-3">إكسسوار خارجي</td>
                        <td class="p-3 text-xs">بدون</td>
                    </tr>';
                    $total_amount+=$ex_acc->price;
                    $sno++; // increase serial AFTER EACH EXTRA ACCESSORY
                }

                $total_amount+=$item->price;
                $total_discount+=$item->discount_amount;
                $deposit_amount+=$item->deposit_amount;
                /** ----------------------------------
                 *  TERMS & CONDITIONS
                 * ----------------------------------*/
                $category = DressCategory::find($item->dress->dress_category_id);

                if ($category) {
                    $terms .= '
                        <h3 class="font-bold mb-2">شروط '.$category->title.'  :</h3>
                        <p class="text-xs text-gray-600 space-y-2 leading-5" style="white-space:pre-line">'
                            .$category->rules.
                        '</p>';
                }
            }

             


             
            return view('booking::booking_delivery_bill', compact('bookings','table_data','terms','setting','total_amount','deposit_amount','total_discount'));
        
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
         
    }

}
