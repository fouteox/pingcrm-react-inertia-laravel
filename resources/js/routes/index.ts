import { queryParams, type QueryParams } from './../wayfinder'
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:21
* @route '/login'
*/
export const login = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ['get','head'],
    url: '/login',
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:21
* @route '/login'
*/
login.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:21
* @route '/login'
*/
login.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: login.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:21
* @route '/login'
*/
login.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: login.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:41
* @route '/logout'
*/
export const logout = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ['post'],
    url: '/logout',
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:41
* @route '/logout'
*/
logout.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:41
* @route '/logout'
*/
logout.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:11
* @route '/'
*/
export const dashboard = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ['get','head'],
    url: '/',
}

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:11
* @route '/'
*/
dashboard.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:11
* @route '/'
*/
dashboard.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:11
* @route '/'
*/
dashboard.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see routes/web.php:47
* @route '/fadogen'
*/
export const fadogen = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: fadogen.url(options),
    method: 'get',
})

fadogen.definition = {
    methods: ['get','head'],
    url: '/fadogen',
}

/**
* @see routes/web.php:47
* @route '/fadogen'
*/
fadogen.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return fadogen.definition.url + queryParams(options)
}

/**
* @see routes/web.php:47
* @route '/fadogen'
*/
fadogen.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: fadogen.url(options),
    method: 'get',
})

/**
* @see routes/web.php:47
* @route '/fadogen'
*/
fadogen.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: fadogen.url(options),
    method: 'head',
})

