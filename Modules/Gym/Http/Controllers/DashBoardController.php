<?php

namespace Modules\Gym\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Gym\Entities\GymAttendance;
use Modules\Gym\Entities\GymPackage;
use Carbon\Carbon;
use App\Contact;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        // Get total members count
        $total_members = Contact::where('type', 'customer')->where('business_id', $business_id)->count();

        // Get active members count (not expired)
        $active_members = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('transactions.type', '=', 'gym_subscription')
            ->where('transactions.gym_package_end_date', '>=', Carbon::now())
            ->distinct()
            ->count('id');

        // Get members registered this month
        $registered_this_month = Contact::where('type', 'customer')
            ->where('business_id', $business_id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        // Get members by category (active subscriptions only)
        $members_by_category = DB::table('transactions')
            ->join('gym_packages', 'transactions.gym_package_id', '=', 'gym_packages.id')
            ->leftJoin('gym_categories', 'gym_packages.gym_category_id', '=', 'gym_categories.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', '=', 'gym_subscription')
            ->where(function($q) {
                $q->whereNull('transactions.gym_package_end_date')
                  ->orWhere('transactions.gym_package_end_date', '>=', Carbon::now());
            })
            ->select(
                'gym_categories.id as category_id',
                'gym_categories.name as category_name',
                DB::raw('COUNT(DISTINCT transactions.contact_id) as member_count')
            )
            ->groupBy('gym_categories.id', 'gym_categories.name')
            ->get();

        // Get members by customer group (active subscriptions only)
        $members_by_customer_group = DB::table('transactions')
            ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->leftJoin('customer_groups', 'contacts.customer_group_id', '=', 'customer_groups.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', '=', 'gym_subscription')
            ->where(function($q) {
                $q->whereNull('transactions.gym_package_end_date')
                  ->orWhere('transactions.gym_package_end_date', '>=', Carbon::now());
            })
            ->select(
                'customer_groups.id as group_id',
                'customer_groups.name as group_name',
                DB::raw('COUNT(DISTINCT transactions.contact_id) as member_count')
            )
            ->groupBy('customer_groups.id', 'customer_groups.name')
            ->get();

        // Get all categories for complete display (even with 0 members)
        $all_categories = GymPackage::where('gym_packages.business_id', $business_id)
            ->leftJoin('gym_categories', 'gym_packages.gym_category_id', '=', 'gym_categories.id')
            ->select('gym_categories.id', 'gym_categories.name')
            ->distinct()
            ->get();
        
        $today = Carbon::today()->toDateString();

        $today_attendances = DB::table('gym_attendances')
            ->join('contacts', 'gym_attendances.contact_id', '=', 'contacts.id') // Join with contacts table
            ->whereDate('gym_attendances.date', $today) // Check if the date is today
            ->whereNull('gym_attendances.out_time') // Check if out_date is null
            ->where('contacts.business_id', $business_id)
            ->select(
                'gym_attendances.*', 
                'contacts.name as contact_name', 
                'contacts.mobile as contact_mobile', 
                'contacts.email as contact_email'
            ) // Select desired columns
            ->get();

        // Get transactions expiring within 7 days
        $expiring_soon = DB::table('transactions')
            ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join('gym_packages', 'transactions.gym_package_id', '=', 'gym_packages.id')
            ->where('transactions.type', '=', 'gym_subscription')
            ->where('transactions.business_id', $business_id)
            ->whereBetween('transactions.gym_package_end_date', [
                Carbon::now(),
                Carbon::now()->addDays(7)
            ])
            ->select(
                'transactions.*',
                'contacts.name as contact_name',
                'contacts.mobile as contact_mobile',
                'gym_packages.name as package_name'
            )
            ->get();

        return view('gym::dashboard.index', compact(
            'total_members',
            'active_members', 
            'registered_this_month',
            'today_attendances',
            'expiring_soon',
            'members_by_category',
            'members_by_customer_group',
            'all_categories'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('gym::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('gym::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('gym::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
