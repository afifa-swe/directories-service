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

class TreasuryAccountControllerTest extends BaseTestCase

{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_index_pagination()
    {
        $mockPaginator = Mockery::mock();
        $mockPaginator->shouldReceive('total')->andReturn(5);

        $query = Mockery::mock();
        $query->shouldReceive('when')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('paginate')->andReturn($mockPaginator);

    $alias = Mockery::mock('alias:App\\Models\\TreasuryAccount');
    $alias->shouldReceive('query')->andReturn($query);

        $controller = new \App\Http\Controllers\TreasuryAccountController();
        $resp = $controller->index(new \Illuminate\Http\Request());
        $this->assertInstanceOf(JsonResponse::class, $resp);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_store_update_and_destroy()
    {
        $data = ['account' => 'ACC1','mfo' => '001','name' => 'N','department' => 'D','currency' => 'USD'];
        $req = Mockery::mock(\App\Http\Requests\StoreTreasuryAccountRequest::class);
        $req->shouldReceive('validated')->andReturn($data);

    $alias2 = Mockery::mock('alias:App\\Models\\TreasuryAccount');
    $model = new class extends \App\Models\TreasuryAccount {
        public function update(array $attributes = [], array $options = []) { return true; }
        public function delete() { return true; }
    };
    $alias2->shouldReceive('create')->andReturn($model);

    $controller = new \App\Http\Controllers\TreasuryAccountController();
        $resp = $controller->store($req);
        $this->assertInstanceOf(JsonResponse::class, $resp);

    $model->created_by = 'owner';

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('other');

        $resp2 = $controller->update($req, $model);
        $this->assertInstanceOf(JsonResponse::class, $resp2);
        $this->assertEquals(403, $resp2->getStatusCode());

        Auth::shouldReceive('id')->andReturn('owner');
        $resp3 = $controller->update($req, $model);
        $this->assertInstanceOf(JsonResponse::class, $resp3);

        // destroy
        Auth::shouldReceive('id')->andReturn('other');
        $resp4 = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp4);
        $this->assertEquals(403, $resp4->getStatusCode());

        Auth::shouldReceive('id')->andReturn('owner');
        $resp5 = $controller->destroy($model);
        $this->assertInstanceOf(JsonResponse::class, $resp5);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_import_dispatches_jobs_and_empty()
    {
        BusFacade::fake();

        $path = sys_get_temp_dir() . '/treasury_test.csv';
        file_put_contents($path, "account,mfo,name,department,currency\nACC1,001,Name1,Dep1,USD\nACC2,002,Name2,Dep2,EUR\n");

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn($path);

    // Mock Validator and job overload
    $validatorMock = Mockery::mock(\Illuminate\Contracts\Validation\Validator::class);
    $validatorMock->shouldReceive('fails')->andReturn(false);
    // Controller may call validate() macro which calls ->validate() on the builder; allow it
    $validatorMock->shouldReceive('validate')->andReturnNull();
    Validator::shouldReceive('make')->andReturn($validatorMock);
    // use Bus fake instead of overloading the job class
    BusFacade::fake();

    // for the success path use a real UploadedFile object
    $request = \Illuminate\Http\Request::create('/', 'POST', [], [], ['file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($path, basename($path))]);

    $controller = new \App\Http\Controllers\TreasuryAccountController();
        $resp = $controller->import($request);
        $this->assertInstanceOf(JsonResponse::class, $resp);

        // empty
        file_put_contents($path, "onlyheader\n");
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
