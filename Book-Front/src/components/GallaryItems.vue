<template>
  <v-card rounded="lg">
    <v-tabs v-model="tab" color="brown-darken-1">
      <v-tab style="font-size: 16px" @click="getListProduct()" :value="'all'"
        >ทั้งหมด</v-tab
      >
      <template v-for="item in catelists">
        <v-tab
          style="font-size: 16px"
          @click="getListProduct(item._i)"
          :value="item._i"
          >{{ item.name }}</v-tab
        >
      </template>
    </v-tabs>

    <v-tabs-window v-model="tab">
      <v-tabs-window-item value="all">
        <v-container fluid>
          <v-row>
            <template v-if="loadProduct">
              <v-col v-for="i in 10" :key="i" cols="12" md="3">
                <v-card class="mx-auto" max-width="400">
                  <v-skeleton-loader :elevation="4" type="card"></v-skeleton-loader>
                </v-card>
              </v-col>
            </template>
            <template v-else>
              <v-col v-for="(item, i) in productsList" :key="i" cols="12" md="3">
                <v-card class="mx-auto" max-width="500" max-height="500" rounded="lg">
                  <image-slide :images="item?.picture"></image-slide>
                  <v-card-title>{{ item?.name }}</v-card-title>
                  <v-card-subtitle class="pt-4"
                    >รหัสหนังสือ : {{ item?.no }}</v-card-subtitle
                  >
                  <v-card-text>
                    <div class="text-caption mb-2">หมวดหมู่</div>
                    <template v-if="item.cate_id.length > 0" v-for="cate in item.cate_id">
                      <v-chip
                        class="me-1"
                        color="gray"
                        rounded="lg"
                        hide-details
                        density="comfortable"
                        size="small"
                        >{{ cate.name }}</v-chip
                      >
                    </template>
                  </v-card-text>

                  <v-card-actions>
                    <div class="d-flex flex-column ga-2 w-100">
                      <div class="d-flex justify-end align-center">
                        <v-chip
                          color="yellow-darken-3"
                          density="comfortable"
                          class="py-5"
                          rounded="lg"
                        >
                          <div class="me-1">฿</div>
                          <div>{{ item?.price }}</div>
                        </v-chip>
                      </div>
                      <div class="d-flex justify-space-between align-center">
                        <v-btn
                          color="brown"
                          border
                          rounded="lg"
                          @click="openDetail(item?._i)"
                          >รายละเอียด</v-btn
                        >
                        <v-btn
                          @click="pushCart(item?._i)"
                          class="ms-auto"
                          color="primary"
                          size="small"
                          variant="outlined"
                          rounded="lg"
                          icon=" mdi-cart-arrow-down"
                        ></v-btn>
                      </div>
                    </div>
                  </v-card-actions>
                </v-card>
              </v-col>
            </template>
          </v-row>
        </v-container>
      </v-tabs-window-item>
      <v-tabs-window-item v-for="item in catelists" :value="item._i" :key="item._i">
        <v-container fluid>
          <v-row>
            <template v-if="loadProduct">
              <v-col v-for="i in 10" :key="i" cols="12" md="3">
                <v-card class="mx-auto" max-width="400">
                  <v-skeleton-loader :elevation="4" type="card"></v-skeleton-loader>
                </v-card>
              </v-col>
            </template>
            <template v-else>
              <template v-if="productsList.length == 0">
                <div style="min-height: 300px" class="mx-auto my-auto">
                  <v-card
                    prepend-icon="mdi-progress-question"
                    width="auto"
                    title="ไม่พบรายการสินค้า"
                    rounded="lg"
                    class="pa-3"
                    flat=""
                  ></v-card>
                </div>
              </template>
              <template v-else>
                <v-col v-for="(item, i) in productsList" :key="i" cols="12" md="3">
                  <v-card class="mx-auto" max-width="500" max-height="500" rounded="lg">
                    <image-slide :images="item?.picture"></image-slide>
                    <v-card-title>{{ item?.name }}</v-card-title>
                    <v-card-subtitle class="pt-4">
                      รหัสหนังสือ : {{ item?.no }}
                    </v-card-subtitle>
                    <v-card-text>
                      <div class="text-caption mb-2">หมวดหมู่</div>
                      <template
                        v-if="item.cate_id.length > 0"
                        v-for="cate in item.cate_id"
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
                    </v-card-text>

                    <v-card-actions>
                      <div class="d-flex flex-column ga-2 w-100">
                        <div class="d-flex justify-end align-center">
                          <v-chip
                            color="yellow-darken-3"
                            density="comfortable"
                            rounded="lg"
                            class="py-5"
                          >
                            <div class="me-1">฿</div>
                            <div>{{ item?.price }}</div>
                          </v-chip>
                        </div>
                        <div class="d-flex justify-space-between">
                          <v-btn
                            color="brown"
                            border
                            rounded="lg"
                            @click="openDetail(item?._i)"
                            >รายละเอียด</v-btn
                          >
                          <v-btn
                            @click="pushCart(item?._i)"
                            class="ms-auto"
                            color="primary"
                            size="small"
                            variant="outlined"
                            rounded="lg"
                            icon=" mdi-cart-arrow-down"
                          ></v-btn>
                        </div>
                      </div>
                    </v-card-actions>
                  </v-card>
                </v-col>
              </template>
            </template>
          </v-row>
        </v-container>
      </v-tabs-window-item>
    </v-tabs-window>
  </v-card>

  <v-dialog v-model="dialog_deatail" width="800">
    <v-card prepend-icon="mdi-book-multiple" title="รายละเอียด">
      <div class="mx-auto d-flex justify-center border pa-2 rounded-lg">
        <v-window show-arrows>
          <template v-slot:prev="{ props }">
            <v-btn
              class="mt-auto mb-2"
              icon="mdi-chevron-left"
              size="small"
              variant="outlined"
              color="white"
              @click="props.onClick"
            >
            </v-btn>
          </template>
          <template v-slot:next="{ props }">
            <v-btn
              class="mt-auto mb-2"
              icon="mdi-chevron-right"
              size="small"
              variant="outlined"
              color="white"
              @click="props.onClick"
            >
            </v-btn>
          </template>
          <v-window-item v-for="n in temp?.picture" :key="`card-${n}`">
            <v-img
              :lazy-src="`https://picsum.photos/10/6?image=${i * n * 5 + 10}`"
              :src="n.path"
              height="205"
              width="400"
              cover
            ></v-img>
          </v-window-item>
        </v-window>
      </div>

      <v-card-title>{{ temp?.name }}</v-card-title>
      <v-card-subtitle class="pt-4">รหัสหนังสือ : {{ temp?.no }}</v-card-subtitle>
      <v-card-text>
        <div class="text-caption mb-2">รายละเอียด</div>
        <div class="mb-2">{{ temp.detail }}</div>
        <div class="text-caption mb-2">หมวดหมู่</div>
        <template v-if="temp?.cate_id?.length > 0" v-for="cate in temp?.cate_id">
          <v-chip
            class="me-1"
            color="gray"
            rounded="lg"
            hide-details
            density="comfortable"
            size="small"
            >{{ cate.name }}</v-chip
          >
        </template>
        <div class="d-flex flex-column ga-2 w-100">
          <div class="d-flex justify-end align-center ga-3">
            <v-chip color="yellow-darken-3" class="py-5" rounded="lg">
              <div class="me-1">฿</div>
              <div>{{ temp?.price }}</div>
            </v-chip>
            <v-btn
              @click="pushCart(item?._i)"
              color="primary"
              size="small"
              variant="outlined"
              rounded="lg"
              icon=" mdi-cart-arrow-down"
            ></v-btn>
          </div>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-btn
          class="ms-auto"
          text="ปิด"
          color="brown-darken-1"
          @click="dialog_deatail = false"
        ></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, defineProps, onMounted, inject } from "vue";
import { useDisplay } from "vuetify";

import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import api from "/utils/axios";
const { getCookie } = useCookie();
const { getItem } = useJWT();
const { mobile } = useDisplay();

const tab = ref(null);
const catelists = ref([]);
const getList = async () => {
  let { data } = await api.get("/api/category/getList");
  if (data?.data.length > 0) {
    catelists.value = data.data;
  }
};

const props = defineProps({
  cateId: {
    type: Number,
    default: null,
  }, // รับรูปภาพจาก Parent
});
const productsList = ref([]);
const getListProduct = async (cate = null) => {
  productsList.value = [];
  loadProduct.value = true;
  let { data } = await api.get("/api/products/getList", {
    params: {
      cate: cate,
    },
  });
  if (data?.data.length > 0) {
    productsList.value = data.data;
  }
  setTimeout(() => {
    loadProduct.value = false;
  }, 500);
};
const loadProduct = ref(false);
const globalitem = ref(getItem(getCookie("jwt")));
const getCountCart = inject("getCountCart");

const pushCart = async (id) => {
  await api
    .post("/api/orders/pushCart", {
      uid: globalitem.value.ui,
      id: id,
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
    })
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      getCountCart();
    })
    .catch((e) => {
      if (e.status == 401) {
        window.location.href = "/login";
      }
    });
};
const dialog_deatail = ref(false);
const temp = ref(null);
const openDetail = (id) => {
  dialog_deatail.value = true;
  let item = productsList.value.filter((e) => e._i == id);
  temp.value = item[0];
};

onMounted(() => {
  getList();
  getListProduct();
});
</script>
