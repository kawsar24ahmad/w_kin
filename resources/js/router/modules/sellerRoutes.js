const SellerComponent = () => import("../../components/admin/sellers/SellerComponent");
const SellerListComponent = () => import("../../components/admin/sellers/SellerListComponent");
const SellerShowComponent = () => import("../../components/admin/sellers/SellerShowComponent");
const SellerOrderDetailsComponent = () => import("../../components/admin/sellers/SellerOrderDetailsComponent");

export default [
    {
        path: "/admin/sellers",
        component: SellerComponent,
        name: "admin.sellers",
        redirect: {name: "admin.sellers.list"},
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "sellers",
            breadcrumb: "sellers",
        },
        children: [
            {
                path: "",
                component: SellerListComponent,
                name: "admin.sellers.list",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "sellers",
                    breadcrumb: "",
                },
            },
            {
                path: "show/:id",
                component: SellerShowComponent,
                name: "admin.sellers.show",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "Sellers",
                    breadcrumb: "view",
                },
            },
            {
                path: "show/:id/:orderId",
                component: SellerOrderDetailsComponent,
                name: "admin.sellers.order.details",
                meta: {
                    isFrontend: false,
                    auth: true,
                    permissionUrl: "sellers",
                    breadcrumb: "order_details",
                },
            },
        ],
    },
];
