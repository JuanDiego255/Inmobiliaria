<script>
import { defineComponent } from 'vue'
import moment from 'moment'

export default defineComponent({
    props: {
        coupon: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            dateFormat: window.coupon.dateFormat || 'YYYY-MM-DD',
            loading: false,
            code: null,
            type: 'percentage',
            value: null,
            quantity: null,
            isUnlimited: true,
            expiresDate: moment().format(this.dateFormat),
            expiresTime: moment().format('HH:mm'),
            neverExpired: true,
            symbol: '%',
        }
    },

    methods: {
        async generateCode() {
            try {
                this.loading = true

                const { data } = await axios.post(route('coupons.generate-coupon'))

                if (data.data) {
                    this.code = data.data

                    return
                }

                Botble.showError(data.message)
            } catch (error) {
                Botble.handleError(error)
            } finally {
                this.loading = false
            }
        },
    },

    mounted() {
        if (this.coupon) {
            const { code, type, value, quantity, expires_date } = this.coupon

            this.code = code
            this.type = type.value
            this.value = value
            this.quantity = quantity
            this.isUnlimited = quantity === null
            this.neverExpired = expires_date === null

            if (expires_date !== null) {
                this.expiresDate = moment(expires_date).format(this.dateFormat)
                this.expiresTime = moment(expires_date).format('HH:mm')
            }
        }
    },

    watch: {
        type(value) {
            if (value === 'percentage') {
                this.symbol = '%'
            } else {
                this.symbol = window.coupon.currency || '$'
            }
        },

        neverExpired() {
            this.expiresDate = moment(this.expiresDate).format(this.dateFormat)
        },
    },
})
</script>

<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">{{ __('coupon.coupon_code') }}</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="code"
                                v-model="code"
                                class="form-control"
                                :placeholder="__('coupon.coupon_code_placeholder')"
                            />
                            <button
                                type="button"
                                :class="{ 'btn btn-secondary': true, 'button-loading': loading }"
                                @click="generateCode"
                            >
                                {{ __('coupon.generate_code_button') }}
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="type">{{ __('coupon.type') }}</label>
                                <select v-model="type" name="type" id="type" class="form-control">
                                    <option value="percentage">{{ __('coupon.types.percentage') }}</option>
                                    <option value="fixed">{{ __('coupon.types.fixed') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="type">{{ __('coupon.value') }}</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        name="value"
                                        v-model="value"
                                        class="form-control"
                                        :placeholder="__('coupon.value_placeholder')"
                                    />
                                    <span class="btn btn-secondary">{{ symbol }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label mb-0">
                            <input type="checkbox" name="is_unlimited" value="1" v-model="isUnlimited" />
                            {{ __('coupon.unlimited') }}
                        </label>

                        <input
                            v-if="!isUnlimited"
                            type="number"
                            name="quantity"
                            v-model="quantity"
                            class="mt-2 form-control"
                            :placeholder="__('coupon.quantity_placeholder')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-1">
                        <div class="form-group date-time-group">
                            <label class="form-label">{{ __('coupon.expires_date') }}</label>
                            <div class="input-group datepicker">
                                <input
                                    type="text"
                                    placeholder="Y-m-d"
                                    data-date-format="Y-m-d"
                                    name="expires_date"
                                    v-model="expiresDate"
                                    class="form-control"
                                    :disabled="neverExpired"
                                    data-input
                                />
                                <button class="btn btn-secondary" type="button" title="toggle" data-toggle>
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group date-time-group">
                            <label class="form-label">{{ __('coupon.expires_time') }}</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    placeholder="hh:mm"
                                    name="expires_time"
                                    v-model="expiresTime"
                                    class="form-control time-picker timepicker timepicker-24"
                                    :disabled="neverExpired"
                                />
                                <button class="btn btn-secondary" type="button" title="toggle" data-toggle>
                                    <i class="fas fa-clock"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label mb-0">
                            <input type="checkbox" name="never_expired" value="1" v-model="neverExpired" />
                            {{ __('coupon.never_expired') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">{{ __('coupon.save_button') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>
