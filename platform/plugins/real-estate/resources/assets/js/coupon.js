import CouponForm from './components/CouponForm.vue'

if (typeof vueApp !== 'undefined') {
    vueApp.booting((vue) => {
        vue.component('v-coupon-form', CouponForm)
    })
}
