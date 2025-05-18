import { queryParams, type QueryParams } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\FetchTranslationsController::__invoke
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
const FetchTranslationsController = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: FetchTranslationsController.url(args, options),
    method: 'get',
})

FetchTranslationsController.definition = {
    methods: ['get','head'],
    url: '/locales/{locale}/translation.json',
}

/**
* @see \App\Http\Controllers\FetchTranslationsController::__invoke
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
FetchTranslationsController.url = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
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

    return FetchTranslationsController.definition.url
            .replace('{locale}', parsedArgs.locale.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FetchTranslationsController::__invoke
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
FetchTranslationsController.get = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: FetchTranslationsController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FetchTranslationsController::__invoke
* @see app/Http/Controllers/FetchTranslationsController.php:13
* @route '/locales/{locale}/translation.json'
*/
FetchTranslationsController.head = (args: { locale: string | number } | [locale: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: FetchTranslationsController.url(args, options),
    method: 'head',
})

export default FetchTranslationsController