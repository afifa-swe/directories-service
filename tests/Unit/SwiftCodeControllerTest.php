<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Bus\Bus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus as BusFacade;
use Mockery;
use Tests\TestCase as BaseTestCase;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

class SwiftCodeControllerTest extends BaseTestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_pagination_and_defaults()
    {
        $mockPaginator = Mockery::mock();
        $mockPaginator->shouldReceive('total')->andReturn(10);

        $query = Mockery::mock();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('paginate')->andReturn($mockPaginator);

    $alias = Mockery::mock('alias:App\\Models\\SwiftCode');
    $alias->shouldReceive('query')->andReturn($query);

        $controller = new \App\Http\Controllers\SwiftCodeController();
        $request = new \Illuminate\Http\Request();
        $resp = $controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $resp);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_store_and_update_validation_and_success()
    {
        $data = ['swift_code' => 'ABC', 'bank_name' => 'B', 'country' => 'C', 'city' => 'City', 'address' => 'Addr'];
        $req = Mockery::mock(\App\Http\Requests\StoreSwiftCodeRequest::class);
        $req->shouldReceive('validated')->andReturn($data);

    $alias2 = Mockery::mock('alias:App\\Models\\SwiftCode');
    $model = new class extends \App\Models\SwiftCode {
            public function update(array $attributes = [], array $options = []) { return true; }
            public function delete() { return true; }
        };
    $alias2->shouldReceive('create')->andReturn($model);

        $controller = new \App\Http\Controllers\SwiftCodeController();
        $resp = $controller->store($req);
        $this->assertInstanceOf(JsonResponse::class, $resp);

    // update
    $model->created_by = 'owner';

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('owner');

        $resp2 = $controller->update($req, $model);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_destroy_authorization_and_not_found()
    {
        $controller = new \App\Http\Controllers\SwiftCodeController();
        $model = new class extends \App\Models\SwiftCode {
            public function delete() { return true; }
        };
    $model->created_by = 'owner';

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('other');

        $resp = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp);
        $this->assertEquals(403, $resp->getStatusCode());

        Auth::shouldReceive('id')->andReturn('owner');
        $resp2 = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_import_dispatches_jobs_and_handles_empty()
    {
        BusFacade::fake();

        $path = sys_get_temp_dir() . '/swift_test.csv';
        file_put_contents($path, "swift_code,bank_name\nUZBEXXX,Bank\nTJSKXXX,Bank2\n");

        // use a real Request with a Symfony UploadedFile so ->all() works
        $request = \Illuminate\Http\Request::create('/', 'POST', [], [], ['file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($path, basename($path))]);

    // Mock Validator::make to return a Validator-compatible mock
    $validatorMock = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
    $validatorMock->shouldReceive('fails')->andReturn(false);
    Validator::shouldReceive('make')->andReturn($validatorMock);

        $controller = new \App\Http\Controllers\SwiftCodeController();
        $resp = $controller->import($request);
        $this->assertInstanceOf(JsonResponse::class, $resp);
        BusFacade::assertDispatched(\App\Jobs\ImportSwiftCodesJob::class);

        // empty
        file_put_contents($path, "onlyheader\n");
        $file2 = new \Symfony\Component\HttpFoundation\File\UploadedFile($path, basename($path));
        $request2 = \Illuminate\Http\Request::create('/', 'POST', [], [], ['file' => $file2]);

    $resp2 = $controller->import($request2);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
        $this->assertEquals(400, $resp2->getStatusCode());
    }
}
