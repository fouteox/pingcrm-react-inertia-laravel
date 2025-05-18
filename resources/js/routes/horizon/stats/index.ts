import { queryParams, type QueryParams } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\DashboardStatsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/DashboardStatsController.php:18
* @route '/horizon/api/stats'
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
    url: '/horizon/api/stats',
}

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardStatsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/DashboardStatsController.php:18
* @route '/horizon/api/stats'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardStatsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/DashboardStatsController.php:18
* @route '/horizon/api/stats'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardStatsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/DashboardStatsController.php:18
* @route '/horizon/api/stats'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const stats = {
    index,
}

export default stats