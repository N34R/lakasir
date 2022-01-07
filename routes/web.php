<?php

use App\Http\Controllers\Settings\General;
use App\Http\Controllers\Transaction\CashDrawer;
use App\Http\Controllers\Transaction\BillPurchasing;
use App\Http\Controllers\Transaction\Purchasing;
use App\Http\Controllers\User\Role;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\ChangePassword;
use App\Http\Controllers\User\Profile;
use App\Http\Controllers\Master\Customer;
use App\Http\Controllers\Master\Group;
use App\Http\Controllers\Master\Supplier;
use App\Http\Controllers\Master\Item;
use App\Http\Controllers\Master\Category;
use App\Http\Controllers\Dashboard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Master\CustomerPoint;
use App\Http\Controllers\Master\CustomerType;
use App\Http\Controllers\Master\PaymentMethod;
use App\Http\Controllers\Settings\DefaultSetting;
use App\Http\Controllers\Settings\General\Company;
use App\Http\Controllers\Settings\General\Date;
use App\Http\Controllers\Transaction\Selling;

/**
 * Jika kamu tidak menyelesaikan ini, kamu punya hutang dengan diri kamu sendiri
 * semangat, semua tujuan yang baik pasti akan menghasilkan output yang baik.
 * proect ini gratis. boleh kamu jual dengan nama kamu, boleh kamu kustom sesuasi keinginan kamu, boleh kamu hapus tulisan ini,
 * dan juga boleh kamu hapus licecnsinya.
 *
 * yang terakhir semoga kita diberikan kemudahan rizki dan hati
 */
Route::get('/update', function (\Codedge\Updater\UpdaterManager $updater) {
    // Check if new version is available
    if ($updater->source()->isNewVersionAvailable()) {
        // Get the current installed version
        echo $updater->source()->getVersionInstalled();

        // Get the new version available
        $versionAvailable = $updater->source()->getVersionAvailable();

        // Create a release
        $release = $updater->source()->fetch($versionAvailable);

        // Run the update process
        $updater->source()->update($release);
    } else {
        echo "No new version available.";
    }
});

Route::get('/xdebug', function ()
{
    $siap = "OK";
    $ok = $siap;
    dd($ok, php_ini_loaded_file());
});

Route::get('/', function () {
    return redirect()->to('/dashboard');
})->middleware(['installed', 'auth']);

Route::view('/completed', 'app.install.completed');

Route::get('/c', function () {
    return view('app.transaction.sellings.cashier');
})->name('cashier')->middleware('installed');

Route::group(['middleware' => ['installed', 'auth']], function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('dashboard/data-selling', Dashboard::class)->name('data-selling');

    Route::group(['prefix' => 'master'], function () {
        Route::delete('/payment_method/bulk-destroy', [PaymentMethod::class, 'bulkDestroy'])->name('payment_method.bulkDestroy');
        Route::resource('/payment_method', PaymentMethod::class);

        Route::delete('/category/bulk-destroy', [Category::class, 'bulkDestroy'])->name('category.bulkDestroy');
        Route::resource('/category', Category::class);

        Route::get('/item/download-template', [Item::class, 'downloadTemplate'])->name('item.download-template');
        Route::post('/item/import', [Item::class, 'importTemplate'])->name('item.import');
        Route::delete('/item/bulk-destroy', [Item::class, 'bulkDestroy'])->name('item.bulkDestroy');
        Route::resource('/item', Item::class);
        Route::put('/item/{item}/update-stock-rate', [Item::class, 'updateStockRate'])->name('item.update-stock-rate');

        Route::get('/supplier/download-template', [Supplier::class, 'downloadTemplate'])->name('supplier.download-template');
        Route::post('/supplier/import', [Supplier::class, 'importTemplate'])->name('supplier.import');
        Route::delete('/supplier/bulk-destroy', [Supplier::class, 'bulkDestroy'])->name('supplier.bulkDestroy');
        Route::resource('/supplier', Supplier::class);

        Route::delete('/group/bulk-destroy', [Group::class, 'bulkDestroy'])->name('group.bulkDestroy');
        Route::resource('/group', Group::class);

        Route::delete('/customer_type/bulk-destroy', [CustomerType::class, 'bulkDestroy'])->name('customer_type.bulkDestroy');
        Route::resource('/customer_type', CustomerType::class);

        Route::delete('/customer/bulk-destroy', [Customer::class, 'bulkDestroy'])->name('customer.bulkDestroy');
        Route::resource('/customer', Customer::class);

        Route::post('/customer-point', [CustomerPoint::class, 'store'])->name('customer-point.store');
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('profile', [Profile::class, 'index'])->name('profile.index');
        Route::post('profile', [Profile::class, 'store'])->name('profile.store');

        Route::get('change_password', [ChangePassword::class, 'index'])->name('change_password.index');
        Route::post('change_password', [ChangePassword::class, 'store'])->name('change_password.store');

        Route::delete('/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('user.bulkDestroy');
        Route::delete('/role/bulk-destroy', [Role::class, 'bulkDestroy'])->name('role.bulkDestroy');
        Route::resource('/role', Role::class);
    });
    Route::resource('/user', UserController::class);

    Route::group(['prefix' => 'transaction'], function () {
        Route::get('/purchasing/{purchasing}/detail/{purchasing-detail}/edit', [Purchasing::class, 'editDetail'])->name('purchasing.detail.edit');
        Route::resource('/purchasing', Purchasing::class);
        Route::post('/purchasing/{purchasing}/paid/', [Purchasing::class, 'updatePaid'])->name('update-paid-purchasing');
        Route::resource('/bill_purchasing', BillPurchasing::class)->only('index');

        Route::get('/cashier', function () {
            get_lang();

            Gate::authorize('browse-selling');

            $token = session()->get('bearer-token');

            return view('app.transaction.sellings.desktop')->with('token', "Bearer $token");
        });

        Route::resource('/selling', Selling::class)->only(['index', 'show']);
    });

    Route::post('/cashdrawer/open', [CashDrawer::class, 'open'])->name('cashdrawer.open');
    Route::post('/cashdrawer/close', [CashDrawer::class, 'close'])->name('cashdrawer.close');

    Route::group(['prefix' => 'setting', 'as' => 's.'], function () {
        Route::resource('/general', General::class)->only(['index']);
        Route::group(['prefix' => '/general', 'as' => 'general.'], function () {
            Route::resource('/company', Company::class)->only(['index', 'store']);
            Route::resource('/date', Date::class)->only(['index', 'store']);
            Route::resource('/currency', Company::class)->only(['index', 'store']);
            Route::resource('/appearance', Company::class)->only(['index', 'store']);
            Route::resource('/plugins', Company::class)->only(['index', 'store']);
        });
        Route::resource('/default', DefaultSetting::class)->only(['index', 'store']);
    });
    Route::resource('/applications', App::class)->only('index');
});

Route::group(['middleware' => 'installed'], function () {
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
    Route::post('register', [RegisterController::class, 'register'])->name('register')->middleware('guest');
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
