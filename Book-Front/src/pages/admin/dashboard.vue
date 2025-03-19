<template>
  <v-card title="Dashboard" solo border rounded="lg">
    <v-card-text>
      <v-row align="center" justify="center" dense>
        <v-col cols="12" md="6">
          <v-card
            variant="outlined"
            color="success"
            class="mx-auto pa-5"
            prepend-icon="mdi-account"
            title="จำนวนผู้ใช้งาน"
          >
            <div class="d-flex justify-end" style="font-size: 50px">
              {{ amountUser }} คน
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card
            variant="outlined"
            color="primary"
            class="mx-auto pa-5"
            prepend-icon="mdi-cash-multiple"
            title="ยอดขายทั้งหมด"
          >
            <div class="d-flex justify-end" style="font-size: 50px">
              {{ sumPrice }} บาท
            </div>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
  <v-card title="Setting Delivery" solo border rounded="lg" class="mt-3">
    <v-card-item>
      <div class="pa-3 d-flex ga-2">
        <div class="w-100">
          <div class="text-caption">จำนวนสินค้า</div>
          <v-text-field
            v-model="amount"
            variant="solo"
            placeholder="จำนวนสินค้า"
            type="number"
          ></v-text-field>
        </div>
        <div class="w-100">
          <div class="text-caption">เงื่อนไข</div>
          <v-select
            v-model="operator"
            variant="solo"
            placeholder="เงื่อนไข"
            :items="setting"
          ></v-select>
        </div>
        <div class="w-100">
          <div class="text-caption">ราคาค่าส่ง (ต่อชิ้น)</div>
          <v-text-field
            v-model="price"
            variant="solo"
            placeholder="จำนวนค่าส่ง"
            type="number"
          ></v-text-field>
        </div>
      </div>
      <div class="d-flex justify-end pa-2">
        <v-btn rounded="lg" color="success" @click="saveSetting">บันทึก</v-btn>
      </div>
    </v-card-item>
    <v-card-action class="pa-2">
      <v-data-table
        class="rounded-lg border"
        :headers="headers"
        :items="lists"
        :length="5"
      >
        <template #item.operater="{ item }">
          {{ setPreview(item.operater) }}
        </template>
      </v-data-table>
    </v-card-action>
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

const setting = ref([
  { title: "น้อยกว่า", value: "<" },
  { title: "มากกว่า", value: ">" },
  { title: "ตั้งแต่ลงมา", value: "<=" },
  { title: "ตั้งแต่ขึ้นไป", value: ">=" },
]);
const headers = ref([
  { align: "start", key: "operater", title: "เงื่อนไข" },
  {
    align: "start",
    key: "amount",
    title: "จำนวนสินค้า",
  },
  {
    align: "start",
    key: "price",
    title: "ราคาค่าส่ง (ต่อชิ้น)",
  },
]);
const setPreview = (item) => {
  return setting.value.filter((e) => e.value == item)[0]?.title;
};
const amountUser = ref(0);
const sumPrice = ref(0);

const getDashboard = async () => {
  let { data } = await api.get("/api/dashboard/getData");
  amountUser.value = data?.data?.user ?? 0;
  sumPrice.value = data?.data?.summary ?? 0;
};
const lists = ref([]);
const getSetting = async () => {
  lists.value = [];
  let { data } = await api.get("/api/dashboard/getSetting");
  if (data.data?.length > 0) lists.value = data.data;
};
const amount = ref(0);
const operator = ref(null);
const price = ref(0);
const saveSetting = async () => {
  await api
    .post("/api/dashboard/saveSetting", {
      amount: amount.value,
      operator: operator.value,
      price: price.value,
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
    })
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      if (result.status) {
        push.success("บันทึกสำเร็จ");
        getSetting();
      }
    });
};
onMounted(() => {
  getDashboard();
  getSetting();
});
</script>
