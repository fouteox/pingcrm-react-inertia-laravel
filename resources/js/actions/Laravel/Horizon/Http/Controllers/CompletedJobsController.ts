import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
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
    url: '/horizon/api/jobs/completed',
}

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const CompletedJobsController = { index }

export default CompletedJobsController