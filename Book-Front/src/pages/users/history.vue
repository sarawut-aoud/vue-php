<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container>
          <v-card title="ประวัติการสั่งซื้อ" border rounded="lg">
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
                <router-custom :theme="theme" :path="'/payments?_i=' + item.order_number">
                  <template #default> #{{ item.order_number }} </template>
                </router-custom>
              </template>
              <template v-slot:item.manage="{ item }">
                <div class="d-flex ga-2 align-center justify-center">
                  <v-btn
                    prepend-icon="mdi-package-variant"
                    color="primary"
                    variant="outlined"
                    >รายละเอียดคำสั่งซื้อ
                  </v-btn>
                  <template v-if="item.status == 'pending'">
                    <v-btn
                      prepend-icon="mdi-cloud-upload"
                      color="warning"
                      variant="outlined"
                      >แนบหลักฐานการชำระเงิน
                    </v-btn>
                    <v-btn
                      @click="cancelOrder(item._i)"
                      :loading="loading_btn_cancel"
                      :disabled="loading_btn_cancel"
                      prepend-icon="mdi-cancel"
                      color="error"
                      variant="outlined"
                      >ยกเลิกคำสั่งซื้อ
                    </v-btn>
                  </template>
                </div>
              </template>
            </v-data-table>
          </v-card>
        </v-container>
      </v-main>
    </v-app>
  </v-responsive>
  <app-footer></app-footer>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import api from "/utils/axios";
import { fa } from "vuetify/locale";
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
  { align: "end", key: "total_price", title: "ยอดที่ต้องชำระ" },
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
  console.log(item);
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

onMounted(() => {
  getHistory();
});
</script>
