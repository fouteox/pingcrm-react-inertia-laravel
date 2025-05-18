import { queryParams, type QueryParams } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see vendor/laravel/horizon/src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: show.url(args, options),
    method: 'post',
})

show.definition = {
    methods: ['post'],
    url: '/horizon/api/jobs/retry/{id}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see vendor/laravel/horizon/src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
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
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see vendor/laravel/horizon/src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
*/
show.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: show.url(args, options),
    method: 'post',
})

const retryJobs = {
    show,
}

export default retryJobs