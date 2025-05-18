import { queryParams, type QueryParams } from './../../wayfinder'
/**
* @see \App\Http\Controllers\FetchTranslationsController::fetch
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
export const fetch = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: fetch.url(args, options),
    method: 'get',
})

fetch.definition = {
    methods: ['get','head'],
    url: '/locales/{locale}/translation.json',
}

/**
* @see \App\Http\Controllers\FetchTranslationsController::fetch
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
fetch.url = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { locale: args }
    }

    if (Array.isArray(args)) {
        args = {
            locale: args[0],
        }
    }

    const parsedArgs = {
        locale: args.locale,
    }

    return fetch.definition.url
            .replace('{locale}', parsedArgs.locale.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FetchTranslationsController::fetch
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
fetch.get = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: fetch.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FetchTranslationsController::fetch
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
fetch.head = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: fetch.url(args, options),
    method: 'head',
})

const i18next = {
    fetch,
}

export default i18next