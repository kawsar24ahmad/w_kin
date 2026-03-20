<template>
<div class="row">

  <div class="col-12">
    <BreadcrumbComponent />
  </div>

  <LoadingComponent :props="loading" />

  <div class="col-12">
    <div class="db-card">

      <div class="db-card-header border-none">
        <h3 class="db-card-title">Withdraw</h3>

        <div class="flex gap-3 items-center">
          <div class="text-lg font-semibold">
            Balance : ৳ {{ formatBalance(wallet_balance) }}
          </div>

          <button v-if="!isAdmin" class="db-btn text-white bg-primary" @click="openWithdrawModal">
            Request Withdraw
          </button>
        </div>
      </div>

      <!-- PAGE MESSAGES -->
      <div v-if="message" class="p-3 mb-3 bg-green-100 text-green-700 rounded">{{ message }}</div>
      <div v-if="error" class="p-3 mb-3 bg-red-100 text-red-700 rounded">{{ error }}</div>

      <div class="db-table-responsive">
        <table class="db-table stripe">
          <thead>
            <tr>
              <th>ID</th>
              <th>Seller</th>
              <th>Date</th>
              <th>Method</th>
              <th>Account</th>
              <th>Amount</th>
              <th>Charge Fee</th>
              <th>Status</th>
              <th v-if="isAdmin">Actions</th>
            </tr>
          </thead>

          <tbody v-if="withdraws.length">
            <tr v-for="withdraw in withdraws" :key="withdraw.id">
              <td>{{ withdraw.id }}</td>
              <td v-if="withdraw.seller">{{ withdraw.seller.name }}</td>
              <td v-else>You</td>
              <td>{{ formatDate(withdraw.created_at) }}</td>
              <td>{{ withdraw.payment_method }}</td>
              <td>{{ withdraw.payment_details }}</td>
              <td>৳ {{ formatBalance(withdraw.amount) }}</td>
              <td>৳ {{ withdraw.charge_fee}}</td>
              <td>
                <span v-if="withdraw.status=='pending'" class="text-yellow-500">Pending</span>
                <span v-if="withdraw.status=='approved'" class="text-green-500">Approved</span>
                <span v-if="withdraw.status=='rejected'" class="text-red-500">Rejected</span>
              </td>
              <td v-if="isAdmin">
                <button
                    v-if="withdraw.status=='pending'"
                    class="db-btn bg-green-500 text-white mr-2"
                    @click="changeStatus(withdraw.id,'approved')">
                    Approve
                </button>

                <button
                    v-if="withdraw.status=='pending'"
                    class="db-btn bg-red-500 text-white"
                    @click="changeStatus(withdraw.id,'rejected')">
                    Reject
                </button>
            </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="8" class="text-center p-5">No Withdraw Found</td>
            </tr>
          </tbody>

        </table>
      </div>

      <!-- Withdraw Modal -->
      <div v-if="showWithdrawModal" class="withdraw-modal">
        <div class="db-card p-5">

          <h3 class="mb-4 text-xl font-bold">Withdraw Request</h3>

          <!-- MODAL SUCCESS -->
          <div v-if="modalMessage" class="p-3 mb-3 bg-green-100 text-green-700 rounded">
            {{ modalMessage }}
          </div>

          <!-- MODAL ERROR -->
          <div v-if="modalError" class="p-3 mb-3 bg-red-100 text-red-700 rounded">
            {{ modalError }}
          </div>

          <div class="mb-4">
            <label class="db-field-title">Amount</label>
            <input v-model="withdraw.amount" type="number" class="db-field-control">
          </div>

          <div class="mb-4">
            <label class="db-field-title">Payment Method</label>
            <select v-model="withdraw.payment_method" class="db-field-control">
              <option value="">Select</option>
              <option value="bkash">Bkash</option>
              <option value="nagad">Nagad</option>
              <option value="bank">Bank</option>
              <option value="cash">Cash</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="db-field-title">Payment Details</label>
            <!-- <input type="text"   v-model="withdraw.payment_details" class="db-field-control"
              placeholder="Bkash/Nagad/Bank Number"> -->
              <textarea name="" id="" v-model="withdraw.payment_details" class="db-field-control" ></textarea>
          </div>

          <div class="flex gap-3">
            <button class="db-btn bg-primary text-white" @click="submitWithdraw">Submit</button>
            <button class="db-btn bg-gray-500 text-white" @click="closeWithdrawModal">Cancel</button>
          </div>

        </div>
      </div>

    </div>
  </div>

</div>
</template>

<script>
import axios from "axios";
import LoadingComponent from "../components/LoadingComponent";
import BreadcrumbComponent from "../components/BreadcrumbComponent";

export default {
  name: "WithdrawListComponent",
  components: { LoadingComponent, BreadcrumbComponent },

  data() {
    return {
      loading: { isActive: false },
      wallet_balance: 0,
      withdraws: [],
      showWithdrawModal: false,
      message: "",
      error: "",
      modalMessage: "",
      modalError: "",
      withdraw: { amount: "", payment_method: "", payment_details: "" },
      isAdmin: false,
    };
  },

  mounted() {
    this.getWithdraws();
  },

  methods: {
    formatBalance(value) {
      return Number(value).toFixed(2);
    },
    formatDate(date) {
      const d = new Date(date);
      return d.toLocaleString("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    openWithdrawModal() {
      this.showWithdrawModal = true;
      this.modalError = "";
      this.modalMessage = "";
    },
    closeWithdrawModal() {
      this.showWithdrawModal = false;
    },
    getWithdraws() {
      this.loading.isActive = true;
      axios.get("/admin/withdraw/list")
        .then(res => {
            console.log(res);

          this.withdraws = res.data.data;
          this.wallet_balance = res.data.balance;
          this.isAdmin = res.data.isAdmin || false; // server must return is_admin
          this.loading.isActive = false;
        })
        .catch(() => {
          this.loading.isActive = false;
          this.error = "Failed to load withdraw requests";
        });
    },
    submitWithdraw() {
      this.modalMessage = "";
      this.modalError = "";

      if (!this.withdraw.amount) { this.modalError = "Enter amount"; return; }
      if (!this.withdraw.payment_method) { this.modalError = "Select payment method"; return; }
      if (!this.withdraw.payment_details) { this.modalError = "Enter payment details"; return; }

      this.loading.isActive = true;

      axios.post("/admin/withdraw/request", this.withdraw)
        .then(res => {
          this.loading.isActive = false;
          this.showWithdrawModal = false;
          this.message = res.data.message;
          this.wallet_balance = res.data.balance;
          this.withdraw = { amount: "", payment_method: "", payment_details: "" };
          this.getWithdraws();
          setTimeout(() => { this.message = ""; }, 3000);
        })
        .catch(err => {
          this.loading.isActive = false;
          this.modalError = err.response?.data?.message || "Something went wrong";
        });
    },

    // Admin: Approve/Reject withdraw
    changeStatus(id, status) {
      this.loading.isActive = true;
      axios.put(`/admin/withdraw/status/${id}`, { status })
        .then(res => {
          this.loading.isActive = false;
          this.message = res.data.message;
          this.getWithdraws();
          setTimeout(() => { this.message = ""; }, 3000);
        })
        .catch(err => {
          this.loading.isActive = false;
          this.error = err.response?.data?.message || "Something went wrong";
        });
    }
  }
};
</script>

<style scoped>
.withdraw-modal {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 999;
}
.withdraw-modal .db-card { width: 400px; }
</style>
