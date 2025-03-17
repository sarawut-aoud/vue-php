<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container border>
          <v-card rounded="lg">
            <v-card-title>
              <div class="d-flex align-center justify-start ga-2">
                <router-custom :path="'/users/history'">
                  <template #default>
                    <v-tooltip text="ย้อนกลับ" location="start">
                      <template v-slot:activator="{ props }">
                        <v-btn v-bind="props" icon="mdi-arrow-left" flat></v-btn>
                      </template>
                    </v-tooltip>
                  </template>
                </router-custom>
                <div>รายละเอียดคำสั่งซื้อ #{{ options?.order_number }}</div>
              </div>
            </v-card-title>
            <v-card-item>
              <template v-if="loadingOrder">
                <template v-for="item in 5" :key="item">
                  <v-card rounded="lg" border="" class="mb-2" height="300">
                    <v-skeleton-loader type="image,article"></v-skeleton-loader>
                  </v-card>
                </template>
              </template>
              <template v-if="items.length == 0">
                <div class="d-flex flex-column ga-2 align-center w-100 justify-center">
                  <v-icon icon=" mdi-progress-question" style="font-size: 60px"></v-icon>
                  ไม่พบคำสั่งซื้อหมายเลข #{{ options?.order_number }}
                </div>
              </template>
              <template v-else v-for="item in items" :key="item">
                <v-card rounded="lg" border="" class="mb-2" height="auto" flat="">
                  <v-card-item>
                    <div class="pa-2 d-flex ga-10 align-start">
                      <image-slide :images="item?.product?.picture"></image-slide>
                      <div class="w-100 d-flex flex-column ga-2">
                        <div class="text-h5">{{ item.product.name }}</div>
                        <div class="text-caption mb-1">รายละเอียด</div>
                        <div class="mb-2">{{ item.product.detail }}</div>
                        <div class="text-caption mb-1">หมวดหมู่</div>

                        <div>
                          <template
                            v-if="item.product?.cate_id.length > 0"
                            v-for="cate in item.product?.cate_id"
                          >
                            <v-chip
                              class="me-1"
                              color="gray"
                              rounded="lg"
                              hide-details
                              density="comfortable"
                              size="small"
                            >
                              {{ cate.name }}
                            </v-chip>
                          </template>
                        </div>
                        <div class="d-flex align-end justify-end ga-2 w-100 pa-2">
                          <div class="d-flex justify-space-between w-25">
                            <div class="w-100">ส่วนลด</div>
                            <div class="w-100 text-end">
                              {{ item?.product?.discount }} บาท
                            </div>
                          </div>
                          <v-divider class="w-100" vertical></v-divider>
                          <div class="d-flex justify-space-between w-25">
                            <div class="w-100">ราคาสุทธิ</div>
                            <div class="w-100 text-end">
                              {{ item?.product?.price }} บาท
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </v-card-item>
                </v-card>
              </template>
            </v-card-item>
            <v-card-action>
              <div class="d-flex flex-column align-end ga-2 w-100 pa-2 text-h5">
                <v-divider class="w-100"></v-divider>
                <div class="d-flex justify-space-between w-25">
                  <div class="w-100">รวมส่วนลด</div>
                  <div class="w-100 text-end">{{ options?.discount }} บาท</div>
                </div>
                <div class="d-flex justify-space-between w-25">
                  <div class="w-100">รวมราคาสุทธิ</div>
                  <div class="w-100 text-end">{{ options?.total_price }} บาท</div>
                </div>
              </div>
              <template v-if="options?.status == 'pending'">
                <QrCode></QrCode>
              </template>
            </v-card-action>
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
import { useRoute } from "vue-router";
import api from "/utils/axios";
import QrCode from "@/components/QrCode.vue";

const route = useRoute();
const order_number = route.params?.order_number;

const { getItem } = useJWT();
const { getCookie } = useCookie();
const globalitem = ref(getItem(getCookie("jwt")));
const theme = ref("light");
const getTheme = (e) => {
  theme.value = e;
};

const loadingOrder = ref(false);
const items = ref([]);
const options = ref(null);
const getOrderPayment = async () => {
  loadingOrder.value = true;
  items.value = [];
  let { data } = await api.get("/api/Orders/getOrderPayment", {
    params: {
      order_number: order_number,
    },
  });
  if (data.data?.length > 0) {
    items.value = data.data;
  }
  options.value = data?.options;
  setTimeout(() => {
    loadingOrder.value = false;
  }, 500);
};
onMounted(() => {
  getOrderPayment();
});
</script>
