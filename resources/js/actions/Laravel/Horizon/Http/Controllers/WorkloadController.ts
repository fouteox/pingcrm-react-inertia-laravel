import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see vendor/laravel/horizon/src/Http/Controllers/WorkloadController.php:15
* @route '/horizon/api/workload'
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
    url: '/horizon/api/workload',
}

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see vendor/laravel/horizon/src/Http/Controllers/WorkloadController.php:15
* @route '/horizon/api/workload'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see vendor/laravel/horizon/src/Http/Controllers/WorkloadController.php:15
* @route '/horizon/api/workload'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see vendor/laravel/horizon/src/Http/Controllers/WorkloadController.php:15
* @route '/horizon/api/workload'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const WorkloadController = { index }

export default WorkloadController