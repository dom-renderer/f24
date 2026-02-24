<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'users.index' => 'View Users',
            'users.create' => 'Create User',
            'users.store' => 'Save User',
            'users.show' => 'Show User',
            'users.edit' => 'Edit User',
            'users.update' => 'Update User',
            'users.destroy' => 'Delete User',
            'users.import' => 'Import User',
            'users.export' => 'Export User',
        
            'roles.index' => 'View Roles',
            'roles.create' => 'Create Role',
            'roles.store' => 'Save Role',
            'roles.show' => 'Show Role',
            'roles.edit' => 'Edit Role',
            'roles.update' => 'Update Role',
            'roles.destroy' => 'Delete Role',
        
            'store-types.index' => 'View Store Types',
            'store-types.create' => 'Create Store Type',
            'store-types.store' => 'Save Store Type',
            'store-types.show' => 'Show Store Type',
            'store-types.edit' => 'Edit Store Type',
            'store-types.update' => 'Update Store Type',
            'store-types.destroy' => 'Delete Store Type',
        
            'model-types.index' => 'View Model Types',
            'model-types.create' => 'Create Model Type',
            'model-types.store' => 'Save Model Type',
            'model-types.show' => 'Show Model Type',
            'model-types.edit' => 'Edit Model Type',
            'model-types.update' => 'Update Model Type',
            'model-types.destroy' => 'Delete Model Type',

            'stores.index' => 'View Stores',
            'stores.create' => 'Create Store',
            'stores.store' => 'Save Store',
            'stores.show' => 'Show Store',
            'stores.edit' => 'Edit Store',
            'stores.update' => 'Update Store',
            'stores.destroy' => 'Delete Store',
            'stores.import' => 'Import Store',
            'stores.export' => 'Export Store',

            'vehicles.index' => 'View Vehicles',
            'vehicles.create' => 'Create Vehicle',
            'vehicles.store' => 'Save Vehicle',
            'vehicles.show' => 'Show Vehicle',
            'vehicles.edit' => 'Edit Vehicle',
            'vehicles.update' => 'Update Vehicle',
            'vehicles.destroy' => 'Delete Vehicle',

            'notification-templates.index' => 'View Notification Templates',
            'notification-templates.create' => 'Create Notification Template',
            'notification-templates.store' => 'Save Notification Template',
            'notification-templates.show' => 'Show Notification Template',
            'notification-templates.edit' => 'Edit Notification Template',
            'notification-templates.update' => 'Update Notification Template',
            'notification-templates.destroy' => 'Delete Notification Template',

            'settings.edit' => 'Settings Edit',
            'settings.update' => 'Settings Update',

            'order-categories.index' => 'View Category',
            'order-categories.create' => 'Create Category',
            'order-categories.store' => 'Save Category',
            'order-categories.edit' => 'Edit Category',
            'order-categories.update' => 'Update Category',
            'order-categories.show' => 'Show Category',
            'order-categories.destroy' => 'Delete Category',

            'order-units.index' => 'View Unit',
            'order-units.create' => 'Create Unit',
            'order-units.store' => 'Save Unit',
            'order-units.edit' => 'Edit Unit',
            'order-units.update' => 'Update Unit',
            'order-units.show' => 'Show Unit',
            'order-units.destroy' => 'Delete Unit',

            'order-products.index' => 'View Product',
            'order-products.create' => 'Create Product',
            'order-products.store' => 'Save Product',
            'order-products.edit' => 'Edit Product',
            'order-products.update' => 'Update Product',
            'order-products.show' => 'Show Product',
            'order-products.destroy' => 'Delete Product',

            'orders.index' => 'View Order',
            'orders.create' => 'Create Order',
            'orders.store' => 'Save Order',
            'orders.edit' => 'Edit Order',
            'orders.update' => 'Update Order',
            'orders.show' => 'Show Order',
            'orders.destroy' => 'Delete Order',
            'orders.status-change' => 'Order Status Change',
            'orders.dashboard' => 'Production Dashboard',
            'orders.reorder' => 'Reorder',

            'currencies.index' => 'View Currencies',
            'currencies.create' => 'Create Currency',
            'currencies.store' => 'Save Currency',
            'currencies.edit' => 'Edit Currency',
            'currencies.update' => 'Update Currency',
            'currencies.show' => 'Show Currency',
            'currencies.destroy' => 'Delete Currency',

            'pricing-tiers.index' => 'View Pricing Tiers',
            'pricing-tiers.create' => 'Create Pricing Tiers',
            'pricing-tiers.store' => 'Save Pricing Tiers',
            'pricing-tiers.edit' => 'Edit Pricing Tiers',
            'pricing-tiers.show' => 'Show Pricing Tiers',
            'pricing-tiers.update' => 'Update Pricing Tiers',
            'pricing-tiers.destroy' => 'Delete Pricing Tiers',

            'bulk-price-management.index' => 'View Bulk Price Management',
            'bulk-price-management.store' => 'Save Bulk Price Management',

            'discount-management.index' => 'View Discount Management',
            'discount-management.create' => 'Create Discount Management',
            'discount-management.store' => 'Save Discount Management',
            'discount-management.show' => 'Show Discount Management',
            'discount-management.edit' => 'Edit Discount Management',
            'discount-management.update' => 'Update Discount Management',
            'discount-management.destroy' => 'Delete Discount Management',

            'grievance-reporting.index' => 'View Grievance',
            'grievance-reporting.create' => 'Create Grievance',
            'grievance-reporting.store' => 'Save Grievance',
            'grievance-reporting.edit' => 'Edit Grievance',
            'grievance-reporting.update' => 'Update Grievance',
            'grievance-reporting.show' => 'Show Grievance',
            'grievance-reporting.destroy' => 'Delete Grievance',
            'grievance-reporting.status-change' => 'Grievance Status Change',

            'utencil-report.index' => 'View Utencil Report',
            'utencil-report.export' => 'Export Utencil Report',

            'handling-instructions.index' => 'View Handling Instruction',
            'handling-instructions.create' => 'Create Handling Instruction',
            'handling-instructions.store' => 'Save Handling Instruction',
            'handling-instructions.edit' => 'Edit Handling Instruction',
            'handling-instructions.update' => 'Update Handling Instruction',
            'handling-instructions.destroy' => 'Delete Handling Instruction',

            'tax-slabs.index' => 'View Tax Slabs',
            'tax-slabs.create' => 'Create Tax Slab',
            'tax-slabs.store' => 'Save Tax Slab',
            'tax-slabs.edit' => 'Edit Tax Slab',
            'tax-slabs.update' => 'Update Tax Slab',
            'tax-slabs.show' => 'Show Tax Slab',
            'tax-slabs.destroy' => 'Delete Tax Slab',

            // 'packaging-materials.index' => 'View Packaging Materials',
            // 'packaging-materials.create' => 'Create Packaging Material',
            // 'packaging-materials.store' => 'Save Packaging Material',
            // 'packaging-materials.edit' => 'Edit Packaging Material',
            // 'packaging-materials.update' => 'Update Packaging Material',
            // 'packaging-materials.show' => 'Show Packaging Material',
            // 'packaging-materials.destroy' => 'Delete Packaging Material',

            // 'services.index' => 'View Services',
            // 'services.create' => 'Create Service',
            // 'services.store' => 'Save Service',
            // 'services.edit' => 'Edit Service',
            // 'services.update' => 'Update Service',
            // 'services.show' => 'Show Service',
            // 'services.destroy' => 'Delete Service',

            'other-items.index' => 'View Other Items',
            'other-items.create' => 'Create Other Item',
            'other-items.store' => 'Save Other Item',
            'other-items.edit' => 'Edit Other Item',
            'other-items.update' => 'Update Other Item',
            'other-items.show' => 'Show Other Item',
            'other-items.destroy' => 'Delete Other Item',
        ];

        $toNotBeDeleted = [];

        foreach ($permissions as $permission => $title) {
            $toNotBeDeleted[] = \Spatie\Permission\Models\Permission::updateOrCreate([
                'name' => $permission
            ],[
                'name' => $permission,
                'guard_name' => 'web',
                'title' => $title
            ])->id;
        }

        if (!empty($toNotBeDeleted)) {
            \Spatie\Permission\Models\Permission::whereNotIn('id', $toNotBeDeleted)->delete();
            DB::table('model_has_permissions')->whereNotIn('permission_id', $toNotBeDeleted)->delete();
            DB::table('role_has_permissions')->whereNotIn('permission_id', $toNotBeDeleted)->delete();
        }
    }
}
