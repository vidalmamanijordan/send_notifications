import type { Ziggy as ZiggyType } from 'ziggy-js'

declare global {
    var Ziggy: ZiggyType

    function route(
        name?: string,
        params?: any,
        absolute?: boolean,
        config?: ZiggyType
    ): string
}

export {}
