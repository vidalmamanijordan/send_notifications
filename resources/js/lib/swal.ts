import Swal from 'sweetalert2'

const swal = Swal.mixin({
    confirmButtonColor: '#2563eb', // azul Tailwind
    cancelButtonColor: '#dc2626',  // rojo Tailwind
    reverseButtons: true,
    buttonsStyling: true,
})

export default swal
