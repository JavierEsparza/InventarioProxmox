<?php

namespace App\Http\Controllers;

use App\Models\FirewallNatRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirewallNatRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = FirewallNatRule::query()
            ->leftJoin('qemus', function ($join) {
                $join->on('firewall_nat_rules.private_ip', '=', 'qemus.ip_address')
                    ->orOn('firewall_nat_rules.private_ip', '=', 'qemus.ip_address_manual');
            })
            ->select('firewall_nat_rules.*',
                'qemus.id as vm_database_id', 'qemus.vmid as vm_id', 'qemus.name as vm_name',
                'qemus.cluster_name as vm_cluster', 'qemus.ip_address as vm_ip',
                'qemus.ip_address_manual as vm_ip_manual')
            ->orderBy('firewall_nat_rules.policy_order')->orderBy('firewall_nat_rules.policy_name');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('firewall_nat_rules.policy_name', 'like', "%{$search}%")
                    ->orWhere('firewall_nat_rules.public_ip', 'like', "%{$search}%")
                    ->orWhere('firewall_nat_rules.private_ip', 'like', "%{$search}%")
                    ->orWhere('firewall_nat_rules.protocol', 'like', "%{$search}%")
                    ->orWhere('qemus.vmid', 'like', "%{$search}%")
                    ->orWhere('qemus.name', 'like', "%{$search}%")
                    ->orWhere('qemus.cluster_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('enabled') && in_array($request->enabled, ['0', '1'], true)) {
            $query->where('firewall_nat_rules.enabled', (bool) $request->enabled);
        }

        if ($request->filled('geolocation')) {
            $query->where('firewall_nat_rules.geolocation', $request->geolocation);
        }

        $rules = $query->paginate(25)->withQueryString();
        $geolocations = FirewallNatRule::query()->whereNotNull('geolocation')->where('geolocation', '<>', '')
            ->distinct()->orderBy('geolocation')->pluck('geolocation');

        $summary = [
            'total' => FirewallNatRule::count(),
            'enabled' => FirewallNatRule::where('enabled', true)->count(),
            'disabled' => FirewallNatRule::where('enabled', false)->count(),
            'unmatched' => DB::table('firewall_nat_rules')->leftJoin('qemus', function ($join) {
                $join->on('firewall_nat_rules.private_ip', '=', 'qemus.ip_address')
                    ->orOn('firewall_nat_rules.private_ip', '=', 'qemus.ip_address_manual');
            })->whereNull('qemus.id')->count(),
        ];

        return view('proxmox.firewall-nat', compact('rules', 'geolocations', 'summary'));
    }
}
