<?php
namespace App\Http\Middleware;

use App\Models\{Order,User,Technician};
use Closure;
use Illuminate\Http\Request;

class EnsureClosedOrderReadOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(),['GET','HEAD','OPTIONS'],true)) return $next($request);
        $actor=auth('sanctum')->user();
        if (!$actor instanceof User && !$actor instanceof Technician) return $next($request);
        $name=$request->route()?->getName() ?? '';
        // These POST endpoints only read data; feedback is the employer-approved exception.
        if (in_array($name,['api.orders.detail','api.orders.extra-services','api.orders.check-discount','api.reviews.submit','api.reviews.dismiss'],true)) return $next($request);
        $id=$request->route('orderId') ?? $request->route('order') ?? $request->input('order_id') ?? $request->input('orderId');
        if ($id instanceof Order) $id=$id->id;
        if (!$id && str_contains($name,'order-reports.update')) $id=\App\Models\TechnicianOrderReport::whereKey($request->route('id'))->value('order_id');
        if (!$id && str_contains($name,'delivery-reports.update')) $id=\App\Models\DeliveryReport::whereKey($request->route('reportId'))->value('order_id');
        if (!$id) return $next($request);
        $owned=Order::whereKey($id)->where($actor instanceof User ? 'user_id' : 'technician_id',$actor->id)->first();
        if ($owned && (int)$owned->status===2) {
            return response()->json(['success'=>false,'message'=>'پرونده مختومه است و فقط امکان مشاهده و ثبت نظر و امتیاز کاربر وجود دارد.','error_code'=>'ORDER_CLOSED_READ_ONLY'],409);
        }
        return $next($request);
    }
}
