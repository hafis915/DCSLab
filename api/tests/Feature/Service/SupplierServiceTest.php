<?php

namespace Tests\Feature\Service;

use Exception;
use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Supplier;
use Tests\ServiceTestCase;
use App\Enums\RecordStatus;
use App\Enums\UnitCategory;
use App\Enums\ProductCategory;
use App\Actions\RandomGenerator;
use App\Services\SupplierService;
use App\Enums\ProductGroupCategory;
use App\Enums\ProductType;
use Database\Seeders\UnitTableSeeder;
use Database\Seeders\BrandTableSeeder;
use Database\Seeders\ProductTableSeeder;
use Database\Seeders\SupplierTableSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Database\Seeders\ProductGroupTableSeeder;
use Illuminate\Contracts\Pagination\Paginator;

class SupplierServiceTest extends ServiceTestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierService = app(SupplierService::class);
        $this->randomGenerator = new RandomGenerator();
    }

    /* #region create */
    public function test_supplier_service_call_create_expect_db_has_record()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;
        
        $productGroupSeeder = new ProductGroupTableSeeder();
        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId, ProductGroupCategory::PRODUCTS->value]);

        $brandSeeder = new BrandTableSeeder();
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId]);

        $unitSeeder = new UnitTableSeeder();
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId, UnitCategory::PRODUCTS->value]);

        $productSeeder = new ProductTableSeeder();
        $productSeeder->callWith(ProductTableSeeder::class, [3, $companyId, ProductCategory::PRODUCTS->value]);
        
        $supplierArr = Supplier::factory()->make([
            'company_id' => $user->companies->first()->id
        ])->toArray();
        
        $picArr = Profile::factory()->make()->toArray();
        $picArr['name'] = strtolower($picArr['first_name'] . $picArr['last_name']) . $this->randomGenerator->generateNumber(1, 999);
        $picArr['email'] = $picArr['name'] . '@something.com';
        $picArr['contact'] = $supplierArr['contact'];
        $picArr['address'] = $supplierArr['address'];
        $picArr['city'] = $supplierArr['city'];
        $picArr['tax_id'] = $supplierArr['tax_id'];
            
        $supplierProductsCount = $this->randomGenerator->generateNumber(1, $company->products()->count());
        $productIds = Product::where([
            ['company_id', '=', $companyId],
            ['brand_id', '!=', null]
        ])->take($supplierProductsCount)->pluck('id');
        
        $productsArr = [];
        foreach ($productIds as $productId) {
            $supplierProduct = [];
            $supplierProduct['product_id'] = $productId;
            $supplierProduct['main_product'] = $this->randomGenerator->generateNumber(0, 1);

            array_push($productsArr, $supplierProduct);
        }

        $result = $this->supplierService->create(
            supplierArr: $supplierArr,
            picArr: $picArr,
            productsArr: $productsArr
        );

        $this->assertDatabaseHas('suppliers', [
            'id' => $result->id,
            'company_id' => $companyId,
            'code' => $result['code'],
            'name' => $result['name'],
            'contact' => $result['contact'],
            'contact' => $result['contact'],
            'city' => $result['city'],
            'payment_term_type' => $result['payment_term_type'],
            'payment_term' => $result['payment_term'],
            'taxable_enterprise' => $result['taxable_enterprise'],
            'tax_id' => $result['tax_id'],
            'status' => $result['status'],
            'remarks' => $result['remarks'],
        ]);

        $this->assertDatabaseHas('profiles', [
            'first_name' => $picArr['first_name'],
            'last_name' => $picArr['last_name'],
            'status' => RecordStatus::ACTIVE->value,
        ]);

        foreach ($productsArr as $product) {
            $this->assertDatabaseHas('supplier_products', [
                'company_id' => $companyId,
                'supplier_id' => $result->id,
                'product_id' => $product['product_id'],
                'main_product' => $product['main_product'],
            ]);
        }
    }

    public function test_supplier_service_call_create_user_pic_expect_sucess()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;
        
        $productGroupSeeder = new ProductGroupTableSeeder();
        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId, ProductGroupCategory::PRODUCTS->value]);

        $brandSeeder = new BrandTableSeeder();
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId]);

        $unitSeeder = new UnitTableSeeder();
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId, UnitCategory::PRODUCTS->value]);

        $productSeeder = new ProductTableSeeder();
        $productSeeder->callWith(ProductTableSeeder::class, [3, $companyId, ProductCategory::PRODUCTS->value]);
        
        $supplierArr = Supplier::factory()->make([
            'company_id' => $user->companies->first()->id
        ])->toArray();
        
        $picArr = Profile::factory()->make()->toArray();
        $picArr['name'] = strtolower($picArr['first_name'] . $picArr['last_name']) . $this->randomGenerator->generateNumber(1, 999);
        $picArr['email'] = $picArr['name'] . '@something.com';
        $picArr['contact'] = $supplierArr['contact'];
        $picArr['address'] = $supplierArr['address'];
        $picArr['city'] = $supplierArr['city'];
        $picArr['tax_id'] = $supplierArr['tax_id'];
            
        $supplierProductsCount = $this->randomGenerator->generateNumber(1, $company->products()->count());
        $productIds = Product::where([
            ['company_id', '=', $companyId],
            ['brand_id', '!=', null]
        ])->take($supplierProductsCount)->pluck('id');
        
        $productsArr = [];
        foreach ($productIds as $productId) {
            $supplierProduct = [];
            $supplierProduct['product_id'] = $productId;
            $supplierProduct['main_product'] = $this->randomGenerator->generateNumber(0, 1);

            array_push($productsArr, $supplierProduct);
        }

        $result = $this->supplierService->create(
            supplierArr: $supplierArr,
            picArr: $picArr,
            productsArr: $productsArr
        );

        $this->assertDatabaseHas('profiles', [
            'first_name' => $picArr['first_name'],
            'last_name' => $picArr['last_name'],
            'status' => RecordStatus::ACTIVE->value,
        ]);

        foreach ($productsArr as $product) {
            $this->assertDatabaseHas('supplier_products', [
                'company_id' => $companyId,
                'supplier_id' => $result->id,
                'product_id' => $product['product_id'],
                'main_product' => $product['main_product'],
            ]);
        }
    }

    public function test_supplier_service_call_create_with_empty_array_parameters_expect_exception()
    {
        $this->expectException(Exception::class);
        $this->supplierService->create(
            [],
            [],
            []
        );
    }
    /* #endregion */

    /* #region list */
    public function test_supplier_service_call_list_with_paginate_true_expect_paginator_object()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $companyId = $user->companies->first()->id;

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [3, $companyId]);

        $result = $this->supplierService->list(
            companyId: $companyId,
            search: '',
            paginate: true,
            page: 1,
            perPage: 10
        );

        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function test_supplier_service_call_list_with_paginate_false_expect_collection_object()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $companyId = $user->companies->first()->id;

        $result = $this->supplierService->list(
            companyId: $companyId,
            search: '',
            paginate: false,
            page: 1,
            perPage: 10
        );

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_supplier_service_call_list_with_nonexistance_companyId_expect_empty_collection()
    {
        $maxId = Company::max('id') + 1;
        $result = $this->supplierService->list(
            companyId: $maxId,
            search: '',
            paginate: false
        );

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    public function test_supplier_service_call_list_with_search_parameter_expect_filtered_results()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;
        
        $productGroupSeeder = new ProductGroupTableSeeder();
        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId, ProductGroupCategory::PRODUCTS->value]);

        $brandSeeder = new BrandTableSeeder();
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId]);

        $unitSeeder = new UnitTableSeeder();
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId, UnitCategory::PRODUCTS->value]);

        $productSeeder = new ProductTableSeeder();
        $productSeeder->callWith(ProductTableSeeder::class, [3, $companyId, ProductCategory::PRODUCTS->value]);

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [10, $companyId]);

        $exampleCount = 3;
        $someProducts = $company->suppliers()->inRandomOrder()->take($exampleCount)->get();
        for ($i = 0; $i < $exampleCount; $i++) {
            $supplier = $someProducts[$i];
            $supplier->name = substr_replace($supplier->name, 'testing', random_int(0, strlen($supplier->name) - 1), 0);
            $supplier->save();
        }

        $result = $this->supplierService->list(
            companyId: $companyId, 
            search: 'testing',
            paginate: true,
            page: 1,
            perPage: 10
        );

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertTrue($result->total() == 3);
    }

    public function test_supplier_service_call_list_with_page_parameter_negative_expect_results()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;
        
        $productGroupSeeder = new ProductGroupTableSeeder();
        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId, ProductGroupCategory::PRODUCTS->value]);

        $brandSeeder = new BrandTableSeeder();
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId]);

        $unitSeeder = new UnitTableSeeder();
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId, UnitCategory::PRODUCTS->value]);

        $productSeeder = new ProductTableSeeder();
        $productSeeder->callWith(ProductTableSeeder::class, [3, $companyId, ProductCategory::PRODUCTS->value]);

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [10, $companyId]);

        $result = $this->supplierService->list(
            companyId: $companyId, 
            search: '',
            paginate: true,
            page: -1,
            perPage: 10
        );

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertTrue($result->total() > 1);
    }

    public function test_supplier_service_call_list_with_perpage_parameter_negative_expect_results()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;
        
        $productGroupSeeder = new ProductGroupTableSeeder();
        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId, ProductGroupCategory::PRODUCTS->value]);

        $brandSeeder = new BrandTableSeeder();
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId]);

        $unitSeeder = new UnitTableSeeder();
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId, UnitCategory::PRODUCTS->value]);

        $productSeeder = new ProductTableSeeder();
        $productSeeder->callWith(ProductTableSeeder::class, [3, $companyId, ProductCategory::PRODUCTS->value]);

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [10, $companyId]);

        $result = $this->supplierService->list(
            companyId: $companyId, 
            search: '',
            paginate: true,
            page: 1,
            perPage: -10
        );

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertTrue($result->total() > 1);
    }
    /* #endregion */

    /* #region read */
    public function test_supplier_service_call_read_expect_object()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $companyId = $user->companies->first()->id;

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [3, $companyId]);

        $supplier = $user->companies->first()->suppliers()->inRandomOrder()->first();

        $result = $this->supplierService->read($supplier);

        $this->assertInstanceOf(Supplier::class, $result);
    }
    /* #endregion */

    /* #region update */
    public function test_supplier_service_call_update_expect_db_updated()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $companyId = $user->companies->first()->id;

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [3, $companyId]);

        $supplier = $user->companies->first()->suppliers()->inRandomOrder()->first();

        $supplierArr = Supplier::factory()->make([
            'company_id' => $user->companies->first()->id
        ])->toArray();

        $productCount = Product::where([
            ['company_id', '=', $companyId],
            ['brand_id', '!=', null]
        ])->count();

        $supplierProductsCount = $this->randomGenerator->generateNumber(1, $productCount);
        $productIds = Product::where([
            ['company_id', '=', $companyId],
            ['brand_id', '!=', null]
        ])->take($supplierProductsCount)->pluck('id');
        
        $productsArr = [];
        foreach ($productIds as $productId) {
            $supplierProduct = [];
            $supplierProduct['product_id'] = $productId;
            $supplierProduct['main_product'] = $this->randomGenerator->generateNumber(0, 1);

            array_push($productsArr, $supplierProduct);
        }
        
        $result = $this->supplierService->update(
            supplier: $supplier,
            supplierArr: $supplierArr,
            productsArr: $productsArr
        );
        
        $this->assertInstanceOf(Supplier::class, $result);

        $this->assertDatabaseHas('suppliers', [
            'id' => $result->id,
            'company_id' => $companyId,
            'code' => $result['code'],
            'name' => $result['name'],
            'contact' => $result['contact'],
            'contact' => $result['contact'],
            'city' => $result['city'],
            'payment_term_type' => $result['payment_term_type'],
            'payment_term' => $result['payment_term'],
            'taxable_enterprise' => $result['taxable_enterprise'],
            'tax_id' => $result['tax_id'],
            'status' => $result['status'],
            'remarks' => $result['remarks'],
        ]);

        // $this->assertDatabaseHas('profiles', [
        //     'first_name' => $picArr['first_name'],
        //     'last_name' => $picArr['last_name'],
        //     'status' => RecordStatus::ACTIVE->value,
        // ]);

        foreach ($productsArr as $product) {
            $this->assertDatabaseHas('supplier_products', [
                'company_id' => $companyId,
                'supplier_id' => $result->id,
                'product_id' => $product['product_id'],
                'main_product' => $product['main_product'],
            ]);
        }
    }

    public function test_supplier_service_call_update_with_empty_array_parameters_expect_exception()
    {
        $this->expectException(Exception::class);
        $this->supplierService->create(
            [],
            [],
            []
        );

        $this->supplierService->update(
            [],
            [],
            []
        );
    }
    /* #endregion */

    /* #region delete */
    public function test_supplier_service_call_delete_expect_bool()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $companyId = $user->companies->first()->id;

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [3, $companyId]);

        $supplier = $user->companies->first()->suppliers()->inRandomOrder()->first();

        $result = $this->supplierService->delete($supplier);

        $this->assertIsBool($result);
        $this->assertTrue($result);
        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id
        ]);
    }
    /* #endregion */

    /* #region others */
    public function test_supplier_service_call_function_generate_unique_code_for_unit_expect_unique_code_returned()
    {
        $user = User::factory()
                    ->has(Company::factory()->setIsDefault(), 'companies')
                    ->create();

        $company = $user->companies->first();
        $companyId = $company->id;

        $supplierSeeder = new SupplierTableSeeder();
        $supplierSeeder->callWith(SupplierTableSeeder::class, [3, $companyId]);

        $code = $this->supplierService->generateUniqueCode();

        $this->assertIsString($code);
        
        $resultCount = $user->companies()->first()->suppliers()->where('code', '=', $code)->count();
        $this->assertTrue($resultCount == 0);
    }

    public function test_supplier_service_call_function_is_unique_code_for_expect_can_detect_unique_code()
    {
        $productGroupSeeder = new ProductGroupTableSeeder();
        $brandSeeder = new BrandTableSeeder();
        $unitSeeder = new UnitTableSeeder();
        $productSeeder = new ProductTableSeeder();
        $supplierSeeder = new SupplierTableSeeder();

        $user = User::factory()
                    ->has(Company::factory()->count(2), 'companies')
                    ->create();

        $company_1 = $user->companies[0];
        $companyId_1 = $company_1->id;

        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId_1, ProductGroupCategory::PRODUCTS->value]);
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId_1]);            
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId_1, UnitCategory::PRODUCTS->value]);
        $productSeeder->callWith(ProductTableSeeder::class, [2, $companyId_1, ProductCategory::PRODUCTS->value]);
        $supplierSeeder->callWith(SupplierTableSeeder::class, [2, $companyId_1]);

        $supplier_company_1 = $company_1->suppliers()->first();
        $supplier_company_1->code = 'test1';
        $supplier_company_1->save();

        $company_2 = $user->companies[1];
        $companyId_2 = $company_2->id;

        $productGroupSeeder->callWith(ProductGroupTableSeeder::class, [3, $companyId_2, ProductGroupCategory::PRODUCTS->value]);
        $brandSeeder->callWith(BrandTableSeeder::class, [3, $companyId_2]);            
        $unitSeeder->callWith(UnitTableSeeder::class, [3, $companyId_2, UnitCategory::PRODUCTS->value]);
        $productSeeder->callWith(ProductTableSeeder::class, [2, $companyId_2, ProductCategory::PRODUCTS->value]);
        $supplierSeeder->callWith(SupplierTableSeeder::class, [2, $companyId_2]);

        $supplier_company_2 = $company_2->suppliers()->first();
        $supplier_company_2->code = 'test2';
        $supplier_company_2->save();

        $this->assertFalse($this->supplierService->isUniqueCode('test1', $companyId_1));
        $this->assertTrue($this->supplierService->isUniqueCode('test2', $companyId_1));
        $this->assertTrue($this->supplierService->isUniqueCode('test3', $companyId_1));
        $this->assertTrue($this->supplierService->isUniqueCode('test1', $companyId_2));
    }
    /* #endregion */
}
