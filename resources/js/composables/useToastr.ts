import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

export function useToastr() {
    const page = usePage();

    watch(
        () => page.props.flash,
        (flash: any) => {
            if (!flash) return;

            if (flash.success) window.toastr.success(flash.success);
            if (flash.error) window.toastr.error(flash.error);
            if (flash.info) window.toastr.info(flash.info);
            if (flash.warning) window.toastr.warning(flash.warning);
        },
        { immediate: true },
    );
}
