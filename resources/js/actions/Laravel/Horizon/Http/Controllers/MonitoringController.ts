import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
export const index = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ['get','head'],
    url: '/horizon/api/monitoring',
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:100
* @route '/horizon/api/monitoring'
*/
export const store = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ['post'],
    url: '/horizon/api/monitoring',
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:100
* @route '/horizon/api/monitoring'
*/
store.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:100
* @route '/horizon/api/monitoring'
*/
store.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:63
* @route '/horizon/api/monitoring/{tag}'
*/
export const paginate = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: paginate.url(args, options),
    method: 'get',
})

paginate.definition = {
    methods: ['get','head'],
    url: '/horizon/api/monitoring/{tag}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:63
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.url = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tag: args }
    }

    if (Array.isArray(args)) {
        args = {
            tag: args[0],
        }
    }

    const parsedArgs = {
        tag: args.tag,
    }

    return paginate.definition.url
            .replace('{tag}', parsedArgs.tag.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:63
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.get = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: paginate.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:63
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.head = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: paginate.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:111
* @route '/horizon/api/monitoring/{tag}'
*/
export const destroy = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ['delete'],
    url: '/horizon/api/monitoring/{tag}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:111
* @route '/horizon/api/monitoring/{tag}'
*/
destroy.url = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tag: args }
    }

    if (Array.isArray(args)) {
        args = {
            tag: args[0],
        }
    }

    const parsedArgs = {
        tag: args.tag,
    }

    return destroy.definition.url
            .replace('{tag}', parsedArgs.tag.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:111
* @route '/horizon/api/monitoring/{tag}'
*/
destroy.delete = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const MonitoringController = { index, store, paginate, destroy }

export default MonitoringController