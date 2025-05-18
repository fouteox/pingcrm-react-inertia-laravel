import { queryParams, type QueryParams } from './../../wayfinder'
/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::csrfCookie
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
export const csrfCookie = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: csrfCookie.url(options),
    method: 'get',
})

csrfCookie.definition = {
    methods: ['get','head'],
    url: '/sanctum/csrf-cookie',
}

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::csrfCookie
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
csrfCookie.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return csrfCookie.definition.url + queryParams(options)
}

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::csrfCookie
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
csrfCookie.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: csrfCookie.url(options),
    method: 'get',
})

/**
* @see \Laravel\Sanctum\Http\Controllers\CsrfCookieController::csrfCookie
* @see vendor/laravel/sanctum/src/Http/Controllers/CsrfCookieController.php:17
* @route '/sanctum/csrf-cookie'
*/
csrfCookie.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: csrfCookie.url(options),
    method: 'head',
})

const sanctum = {
    csrfCookie,
}

export default sanctum