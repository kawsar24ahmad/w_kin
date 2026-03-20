import axios from 'axios'
import appService from "../../services/appService";


export const productsReport = {
    namespaced: true,
    state: {
        lists: [],
        page: {},
        pagination: [],
        show: {},
        temp: {
            temp_id: null,
            isEditing: false,
        },
        productsReportOverview: [],
        sellerProductsList: {},
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        sellerProducts: function (state) {
            return state.sellerProductsList;
        },

        pagination: function (state) {
            return state.pagination
        },
        page: function (state) {
            return state.page;
        },
        show: function (state) {
            return state.show;
        },
        temp: function (state) {
            return state.temp;
        },
        productsReportOverview: function (state) {
            return state.productsReportOverview;
        },
    },
    actions: {
        sellerProducts: function (context, sellerId) {
            return new Promise((resolve, reject) => {

                let url = `admin/seller/${sellerId}/products`;

                axios.get(url)
                    .then((res) => {

                        console.log(res.data);

                        context.commit('sellerProducts', res.data);

                        resolve(res);

                    })
                    .catch((err) => reject(err));

            });
        },
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/products-report';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    console.log(res.data);

                    if (typeof payload.vuex === "undefined" || payload.vuex === true) {
                        context.commit('lists', res.data.data);
                        context.commit('page', res.data.meta);
                        context.commit('pagination', res.data);
                    }

                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        reset: function (context) {
            context.commit('reset');
        },

        productsReportOverview: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/products-report/overview';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {

                    context.commit('productsReportOverview', res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },

        export: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/products-report/export';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, { responseType: 'blob' }).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        exportPdf: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/products-report/export-pdf';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, { responseType: 'blob' }).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
    },
    mutations: {
        sellerProducts: function (state, payload) {
            state.sellerProductsList = payload;
        },
        lists: function (state, payload) {
            state.lists = payload
        },
        pagination: function (state, payload) {
            state.pagination = payload;
        },
        page: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.page = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total
                }
            }
        },
        show: function (state, payload) {
            state.show = payload;
        },
        temp: function (state, payload) {
            state.temp.temp_id = payload;
            state.temp.isEditing = true;
        },
        reset: function (state) {
            state.temp.temp_id = null;
            state.temp.isEditing = false;
        },
        productsReportOverview: function (state, payload) {
            state.productsReportOverview = payload
        },
    },
}
