import { queryParams, type QueryParams } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::store
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:29
* @route '/login'
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
    url: '/login',
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::store
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:29
* @route '/login'
*/
store.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::store
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:29
* @route '/login'
*/
store.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

const login = {
    store,
}

export default login