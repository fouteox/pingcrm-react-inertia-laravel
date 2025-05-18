import { queryParams, type QueryParams, validateParameters } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\HomeController::index
* @see vendor/laravel/horizon/src/Http/Controllers/HomeController.php:14
* @route '/horizon/{view?}'
*/
export const index = (args?: { view?: string | number } | [view: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ['get','head'],
    url: '/horizon/{view?}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\HomeController::index
* @see vendor/laravel/horizon/src/Http/Controllers/HomeController.php:14
* @route '/horizon/{view?}'
*/
index.url = (args?: { view?: string | number } | [view: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { view: args }
    }

    if (Array.isArray(args)) {
        args = {
            view: args[0],
        }
    }

    validateParameters(args, [
        "view",
    ])

    const parsedArgs = {
        view: args?.view,
    }

    return index.definition.url
            .replace('{view?}', parsedArgs.view?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\HomeController::index
* @see vendor/laravel/horizon/src/Http/Controllers/HomeController.php:14
* @route '/horizon/{view?}'
*/
index.get = (args?: { view?: string | number } | [view: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\HomeController::index
* @see vendor/laravel/horizon/src/Http/Controllers/HomeController.php:14
* @route '/horizon/{view?}'
*/
index.head = (args?: { view?: string | number } | [view: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(args, options),
    method: 'head',
})

const HomeController = { index }

export default HomeController