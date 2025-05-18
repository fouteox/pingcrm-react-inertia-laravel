import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
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
    url: '/horizon/api/jobs/silenced',
}

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const SilencedJobsController = { index }

export default SilencedJobsController