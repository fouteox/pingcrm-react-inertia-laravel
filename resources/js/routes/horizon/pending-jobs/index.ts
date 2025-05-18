import { queryParams, type QueryParams } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
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
    url: '/horizon/api/jobs/pending',
}

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const pendingJobs = {
    index,
}

export default pendingJobs