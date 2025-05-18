import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::show
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
export const show = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ['get','head'],
    url: '/sanctum/csrf-cookie',
}

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::show
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
show.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::show
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
show.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::show
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
show.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: show.url(options),
    method: 'head',
})

const CsrfCookieController = { show }

export default CsrfCookieController