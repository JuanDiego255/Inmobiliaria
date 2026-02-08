<template>
    <div>
        <div class="form-group mb-3" v-for="(item, index) in items">
            <div class="row">
                <div class="col-md-3 col-sm-5">
                    <div class="form-group mb-3">
                        <div class="ui-select-wrapper">
                            <select
                                :name="'facilities[' + index + 1 + '][id]'"
                                class="select-search-full ui-select"
                                @change="removeSelectedItem"
                            >
                                <option value="">{{ __('select_facility') }}</option>
                                <option
                                    v-for="(facility, index) in facilities"
                                    :key="index"
                                    :value="facility.id"
                                    :selected="facility.id === item.id"
                                >
                                    {{ facility.name }}
                                </option>
                            </select>
                            <svg class="svg-next-icon svg-next-icon-size-16">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M10 16l-4-4h8l-4 4zm0-12L6 8h8l-4-4z"></path>
                                </svg>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-5">
                    <div class="form-group mb-3">
                        <input
                            type="text"
                            :name="'facilities[' + index + 1 + '][distance]'"
                            v-model="item.distance"
                            class="form-control"
                            :placeholder="__('distance')"
                        />
                    </div>
                </div>
                <div class="col-md-3 col-sm-2">
                    <button class="btn btn-warning" type="button" @click="deleteRow(index)">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-group mb-3">
            <button class="btn btn-info" type="button" @click="addRow">{{ __('add_new') }}</button>
        </div>
    </div>
</template>

<script>
const { nextTick } = Vue

export default {
    data: function () {
        return {
            items: [{ id: '', distance: '' }],
        }
    },
    mounted() {
        if (this.selected_facilities.length) {
            this.items = []
            for (const item of this.selected_facilities) {
                this.items.push({ id: item.id, distance: item.distance })
            }
        }
    },
    props: {
        selected_facilities: {
            type: Array,
            default: () => [],
        },
        facilities: {
            type: Array,
            default: () => [],
        },
    },

    methods: {
        addRow: function () {
            this.items.push({ id: '', distance: '' })

            nextTick(() => {
                if (window.Botble) {
                    window.Botble.initResources()
                }
            })
        },
        deleteRow: function (index) {
            this.items.splice(index, 1)
        },
        removeSelectedItem: function () {
            for (let i = 0; i < this.facilities.length; i++) {
                for (const item of this.items) {
                    if (item.id === this.facilities[i].id) {
                        this.facilities.slice(i, 1)
                    }
                }
            }
        },
    },
}
</script>
