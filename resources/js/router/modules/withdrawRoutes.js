const WithdrawComponent = () => import("../../components/admin/withdraw/Withdraw");

export default [
    {
        path: '/admin/withdraw',
        component: WithdrawComponent,
        name: 'admin.withdraw',
        meta: {
            isFrontend: false,
            auth: true,

            breadcrumb: 'Withdraw'
        }
    }
]
