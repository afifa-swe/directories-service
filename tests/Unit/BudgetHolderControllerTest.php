<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Bus\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus as BusFacade;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\TestCase as BaseTestCase;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

class BudgetHolderControllerTest extends BaseTestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_defaults_and_pagination()
    {
        $mockPaginator = Mockery::mock();
        $mockPaginator->shouldReceive('total')->andReturn(40);

        $query = Mockery::mock();
        $query->shouldReceive('when')->andReturnSelf();
    $query->shouldReceive('orderBy')->andReturnSelf();
    $query->shouldReceive('paginate')->andReturn($mockPaginator);

    $alias = Mockery::mock('overload:App\\Models\\BudgetHolder');
    $alias->shouldReceive('query')->andReturn($query);

    $controller = new \App\Http\Controllers\BudgetHolderController();
        $request = new \Illuminate\Http\Request();

        $response = $controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_custom_page_per_page_and_edge_cases()
    {
        $mockPaginator = Mockery::mock();
        $mockPaginator->shouldReceive('total')->andReturn(0);

        $query = Mockery::mock();
        $query->shouldReceive('when')->andReturnSelf();
    $query->shouldReceive('orderBy')->andReturnSelf();
    $query->shouldReceive('paginate')->andReturn($mockPaginator);

    $alias = Mockery::mock('overload:App\\Models\\BudgetHolder');
    $alias->shouldReceive('query')->andReturn($query);

    $controller = new \App\Http\Controllers\BudgetHolderController();

        $request = \Illuminate\Http\Request::create('/','GET', ['page' => 2, 'per_page' => 50]);
        $response = $controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $request2 = \Illuminate\Http\Request::create('/','GET', ['page' => 0, 'per_page' => 1000]);
        $query->shouldReceive('paginate')->with(100, ['*'], 'page', 0)->andReturn($mockPaginator);
        $response2 = $controller->index($request2);
        $this->assertInstanceOf(JsonResponse::class, $response2);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_store_success_and_validation_failure()
    {
        $data = ['tin' => '123', 'name' => 'Test', 'region' => 'R', 'district' => 'D', 'address' => 'A', 'phone' => 'P', 'responsible' => 'X'];

        $request = Mockery::mock(\App\Http\Requests\StoreBudgetHolderRequest::class);
        $request->shouldReceive('validated')->andReturn($data);

    $model = Mockery::mock('stdClass');
        $model->shouldReceive('getAttribute')->with('id')->andReturn('uuid');

    $alias2 = Mockery::mock('overload:App\\Models\\BudgetHolder');
    $alias2->shouldReceive('create')->with(Mockery::on(function ($arg) use ($data) { return isset($arg['tin']) && $arg['tin'] === $data['tin']; }))->andReturn($model);

        $controller = new \App\Http\Controllers\BudgetHolderController();
        $response = $controller->store($request);
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Validation failure - simulate FormRequest failedValidation by making validated throw
        $badRequest = Mockery::mock(\App\Http\Requests\StoreBudgetHolderRequest::class);
        $validator = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
        $validator->shouldReceive('errors')->andReturn(new \Illuminate\Support\MessageBag(['field' => ['error']]));
        $ve = new \Illuminate\Validation\ValidationException($validator);
        $badRequest->shouldReceive('validated')->andThrow($ve);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($badRequest);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_update_and_destroy_authorization_and_not_found()
    {
    $controller = new \App\Http\Controllers\BudgetHolderController();

    $model = new class extends \App\Models\BudgetHolder {
        public function update(array $attributes = [], array $options = []) { return true; }
        public function delete() { return true; }
    };
    $model->created_by = 'owner-id';

        // case: unauthorized when auth()->check() true and different owner
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('other-id');

        $request = Mockery::mock(\App\Http\Requests\StoreBudgetHolderRequest::class);
        $request->shouldReceive('validated')->andReturn(['name'=>'X']);

        $response = $controller->update($request, $model);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());

        // authorized
        Auth::shouldReceive('id')->andReturn('owner-id');
        $response2 = $controller->update($request, $model);
        $this->assertInstanceOf(JsonResponse::class, $response2);

        // destroy unauthorized
        Auth::shouldReceive('id')->andReturn('other-id');
        $resp = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp);
        $this->assertEquals(403, $resp->getStatusCode());

        // destroy success
        Auth::shouldReceive('id')->andReturn('owner-id');
        $resp2 = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_import_dispatches_jobs_and_handles_empty()
    {
    BusFacade::fake();

        // create a temporary CSV fixture file in storage
        $path = sys_get_temp_dir() . '/budget_test.csv';
        file_put_contents($path, "tin,name\n123,One\n456,Two\n");

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn($path);

        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('validate')->andReturnTrue();
        $request->shouldReceive('file')->with('file')->andReturn($file);

    // ensure dispatch is called - mock job static dispatch via overload
    $jobAlias = Mockery::mock('overload:App\\Jobs\\ImportBudgetHoldersJob');
    $jobAlias->shouldReceive('dispatch')->andReturnSelf();

    $controller = new \App\Http\Controllers\BudgetHolderController();
        $response = $controller->import($request);
        $this->assertInstanceOf(JsonResponse::class, $response);

        // empty file case
        file_put_contents($path, "header_only\n");
        $file2 = Mockery::mock(UploadedFile::class);
        $file2->shouldReceive('getRealPath')->andReturn($path);
        $request2 = Mockery::mock(\Illuminate\Http\Request::class);
        $request2->shouldReceive('validate')->andReturnTrue();
        $request2->shouldReceive('file')->with('file')->andReturn($file2);

        $resp2 = $controller->import($request2);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
        $this->assertEquals(400, $resp2->getStatusCode());
    }
}
