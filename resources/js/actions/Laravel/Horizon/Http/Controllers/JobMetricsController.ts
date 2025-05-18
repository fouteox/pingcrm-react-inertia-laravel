import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
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
    url: '/horizon/api/metrics/jobs',
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ['get','head'],
    url: '/horizon/api/metrics/jobs/{id}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see vendor/laravel/horizon/src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: show.url(args, options),
    method: 'head',
})

const JobMetricsController = { index, show }

export default JobMetricsController