<template>
  <v-card title="ออเดอร์" border rounded="lg">
    <template v-slot:text>
      <v-text-field
        v-model="search"
        label="ค้นหา..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        hide-details
        single-line
        density="compact"
        rounded="lg"
      ></v-text-field>
    </template>
    <v-data-table :headers="headers" :items="lists" :search="search">
      <template v-slot:item.status="{ item }">
        <status-chip :status="item.status" />
      </template>
      <template v-slot:item.order_number="{ item }">
        <router-custom :theme="theme" :path="'/payments/' + item.order_number">
          <template #default> #{{ item.order_number }} </template>
        </router-custom>
      </template>
      <template v-slot:item.payment.picture="{ item }">
        <v-menu :close-on-content-click="false" location="end">
          <template v-slot:activator="{ props }">
            <div class="d-flex justify-center pa-2 w-100">
              <div style="width: 100px">
                <v-img v-bind="props" :src="item.payment.picture" width="50"></v-img>
              </div>
            </div>
          </template>

          <v-card min-width="600">
            <v-img v-bind="props" :src="item.payment.picture" cover width="800"></v-img>
          </v-card>
        </v-menu>
      </template>
      <template #item.payment.address="{ item }">
        <v-menu :close-on-content-click="false" location="end">
          <template v-slot:activator="{ props }">
            <div
              v-bind="props"
              style="
                width: 100px;
                text-overflow: ellipsis;
                white-space: nowrap;
                overflow: hidden;
              "
            >
              {{ item.payment.address }}
            </div>
          </template>

          <v-card min-width="300" class="pa-3" rounded="lg">
            <div>{{ item.payment.address }}</div>
          </v-card>
        </v-menu>
      </template>
      <template v-slot:item.manage="{ item }">
        <div class="d-flex ga-2 align-center justify-center">
          <template v-if="item.status == 'paid'">
            <v-btn
              @click="successOrder(item._i)"
              prepend-icon="mdi-check-circle"
              variant="outlined"
              color="success"
              >ยืนยันการชำระเงิน</v-btn
            >
            <v-btn
              variant="outlined"
              color="red"
              @click="cancelOrder(item._i)"
              :loading="loading_btn_cancel"
              :disabled="loading_btn_cancel"
              >ยกเลิกรายการ</v-btn
            >
          </template>
        </div>
      </template>
    </v-data-table>
  </v-card>

  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref, onMounted, watch, inject } from "vue";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import api from "/utils/axios";
const { getItem } = useJWT();
const { getCookie } = useCookie();
const globalitem = ref(getItem(getCookie("jwt")));
const theme = ref("light");

const getTheme = (e) => {
  theme.value = e;
};
const search = ref(null);
const headers = ref([
  { align: "start", key: "status", title: "สถานะ" },
  {
    align: "start",
    key: "order_number",
    title: "เลขคำสั่งซื้อ",
  },
  { key: "date", title: "วันที่สั่งซื้อ" },
  { align: "center", key: "total_price", title: "ยอดที่ต้องชำระ" },
  { align: "center", key: "payment.type", title: "ประเภทการชำระเงิน" },
  { align: "center", key: "payment.picture", title: "หลักฐานการชำระเงิน" },
  { align: "center", key: "payment.address", title: "ที่อยู่จัดส่ง" },
  { key: "manage", title: "" },
]);

const lists = ref([]);
const loadingtbl = ref(false);
const getHistory = async () => {
  lists.value = [];
  loadingtbl.value = true;
  await api
    .get("/api/orders/getHistorys")
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      if (result?.data.length > 0) {
        lists.value = result.data;
      }
    })
    .catch((e) => {
      console.error(e);
    });
  loadingtbl.value = false;
};
const loading_btn_cancel = ref(false);
const cancelOrder = async (id) => {
  loading_btn_cancel.value = true;
  let item = lists.value.filter((e) => e._i == id)[0];

  Swal.fire({
    title: `ต้องการยกเลิกคำสั่งซื้อเลขที่ #${item.order_number} หรือไม่ ?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "ตกลง",
    cancelButtonText: "ยกเลิก",
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api
        .post("/api/orders/cancelOrder/" + id, {
          csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
        })
        .then((r) => {
          return r.data;
        })
        .then((result) => {
          if (result.status) {
            push.success("ยกเลิกคำสั่งซื้อสำเร็จ");
            loading_btn_cancel.value = false;
            getHistory();
          }
        });
    } else {
      loading_btn_cancel.value = false;
    }
  });
};
const getlistsCount = inject("getlistsCount");
const loading_btn_success = ref(false);
const successOrder = async (id) => {
  loading_btn_success.value = true;
  let item = lists.value.filter((e) => e._i == id)[0];
  Swal.fire({
    title: `ยืนยันการชำระเงินคำสั่งซื้อเลขที่ #${item.order_number} หรือไม่ ?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "ตกลง",
    cancelButtonText: "ยกเลิก",
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api
        .post("/api/orders/successOrder/" + id, {
          csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
        })
        .then((r) => {
          return r.data;
        })
        .then(async (result) => {
          if (result.status) {
            push.success("ทำรายการสำเร็จ");
            loading_btn_success.value = false;
            getHistory();
            getlistsCount();
          }
        });
    } else {
      loading_btn_success.value = false;
    }
  });
};
onMounted(() => {
  getHistory();
});
</script>
