import axios from "axios";
import appService from "../../services/appService";

export const seller = {
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
        myOrders: [],
        orderPage: {},
        orderPagination: [],
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        pagination: function (state) {
            return state.pagination;
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
        myOrders: function (state) {
            return state.myOrders;
        },

        orderPagination: function (state) {
            return state.orderPagination;
        },
        orderPage: function (state) {
            return state.orderPage;
        },

    },
    actions: {
          bulkDelete({ commit }, payload) {
            // payload = { ids: [1,2,3] }

            return axios.post('/admin/seller/bulk-delete', payload);
        },
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = "admin/seller";
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    console.log(res);

                    if (typeof payload.vuex === "undefined" || payload.vuex === true) {
                        context.commit("lists", res.data.data);
                        context.commit("page", res.data.meta);
                        context.commit("pagination", res.data);
                    }
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        save: function (context, payload) {
    return new Promise((resolve, reject) => {
        let url = "/admin/seller";
        let formData = new FormData();

        // formData বানানো
        Object.keys(payload.form).forEach(key => {
            let value = payload.form[key];
            if (value instanceof File) {
                formData.append(key, value, value.name);
            } else if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        // যদি editing হয়
        if (context.state.temp.isEditing) {
            url = `/admin/seller/${context.state.temp.temp_id}`;
            formData.append('_method', 'PUT'); // Laravel method spoofing
        }

        axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then((res) => {
            // তালিকা refresh
            context.dispatch("lists", payload.search);
            context.commit("reset");
            resolve(res);
        })
        .catch((err) => {
            reject(err);
        });
    });
},
        // save: function (context, payload) {

        //     return new Promise((resolve, reject) => {
        //         console.log(this.state["seller"].temp);

        //         let method = axios.post;
        //         let url = "/admin/seller";
        //         console.log(payload.form);

        //         let formData = new FormData();

        //         Object.keys(payload.form).forEach(key => {
        //             let value = payload.form[key];

        //             if (value instanceof File) {
        //                 // ফাইল হলে নাম সহ append কর
        //                 formData.append(key, value, value.name);
        //             } else {
        //                 formData.append(key, value);
        //             }
        //         });

        //         //  debug করার জন্য
        //         for (let [key, value] of formData.entries()) {
        //             console.log(key, value);
        //         }
        //         if (this.state["seller"].temp.isEditing) {
        //             method = axios.put;
        //             url = `/admin/seller/${this.state["seller"].temp.temp_id}`;
        //             formData = payload.form;
        //         }


        //         method(url, formData).then((res) => {
        //             context.dispatch("lists", payload.search).then().catch();
        //             context.commit("reset");
        //             resolve(res);
        //         }).catch((err) => {
        //             reject(err);
        //         });
        //     });
        // },
        edit: function (context, payload) {
            context.commit("temp", payload);
        },
        destroy: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.delete(`admin/employee/${payload.id}`).then((res) => {
                    context.dispatch("lists", payload.search).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        show: function (context, payload) {

            return new Promise((resolve, reject) => {
                axios.get(`admin/seller/show/${payload}`).then((res) => {
                    context.commit("show", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        reset: function (context) {
            context.commit("reset");
        },
        export: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/employee/export';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, {responseType: 'blob'}).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        changePassword: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post(`/admin/employee/change-password/${payload.id}`, payload.form).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        changeImage: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post(`/admin/employee/change-image/${payload.id}`, payload.form, {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }
                ).then((res) => {
                    context.commit("show", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        myOrders: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `admin/employee/my-order/${payload.id}`;
                if (payload.search) {
                    url = url + appService.requestHandler(payload.search);
                }
                axios.get(url).then((res) => {
                        if (typeof payload.vuex === "undefined" || payload.vuex === true) {
                            context.commit("myOrders", res.data.data);
                            context.commit("orderPage", res.data.meta);
                            context.commit("orderPagination", res.data);
                        }

                        resolve(res);
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
        },
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        },
        pagination: function (state, payload) {
            state.pagination = payload;
        },
        page: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.page = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
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
        myOrders: function (state, payload) {
            state.myOrders = payload;
        },
        orderPagination: function (state, payload) {
            state.orderPagination = payload;
        },
        orderPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.orderPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
    },
};
