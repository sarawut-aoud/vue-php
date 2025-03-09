<template>
  <v-card>
    <v-tabs v-model="tab" color="info">
      <v-tab @click="getListProduct()" :value="'all'">ทั้งหมด</v-tab>
      <template v-for="item in catelists">
        <v-tab @click="getListProduct(item._i)" :value="item._i">{{ item.name }}</v-tab>
      </template>
    </v-tabs>

    <v-tabs-window v-model="tab">
      <v-tabs-window-item value="all">
        <v-container fluid>
          <v-row>
            <template v-if="loadProduct">
              <v-col v-for="i in 10" :key="i" cols="12" md="2">
                <v-card class="mx-auto" max-width="400">
                  <v-skeleton-loader :elevation="4" type="card"></v-skeleton-loader>
                </v-card>
              </v-col>
            </template>
            <template v-else>
              <v-col v-for="(item, i) in productsList" :key="i" cols="12" md="2">
                <v-card class="mx-auto" max-width="400" rounded="lg">
                  <v-window show-arrows>
                    <v-window-item v-for="n in item?.picture" :key="`card-${n}`">
                      <v-img
                        :lazy-src="`https://picsum.photos/10/6?image=${i * n * 5 + 10}`"
                        :src="n.path"
                        height="205"
                        width="400"
                        cover
                      ></v-img>
                    </v-window-item>
                  </v-window>
                  <v-card-title>{{ item?.name }}</v-card-title>
                  <v-card-subtitle class="pt-4"
                    >รหัสหนังสือ : {{ item?.no }}</v-card-subtitle
                  >
                  <v-card-text>
                    <div class="text-caption mb-2">รายละเอียด</div>
                    <div class="mb-2">{{ item?.detail ? item?.detail : "-" }}</div>
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
                        <v-icon icon="mdi-currency-usd" size="small"></v-icon>
                        <div>50</div>
                      </div>
                      <div class="d-flex justify-space-between">
                        <v-btn text="Share">รายละเอียด</v-btn>
                        <v-btn
                          class="ms-auto"
                          color="primary"
                          text="Explore"
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
              <v-col v-for="i in 10" :key="i" cols="12" md="2">
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
                    width="500"
                    title="ไม่พบรายการสินค้า"
                    rounded="lg"
                    class="pa-3"
                    flat=""
                  ></v-card>
                </div>
              </template>
              <template v-else>
                <v-col v-for="(item, i) in productsList" :key="i" cols="12" md="2">
                  <v-card class="mx-auto" max-width="400" rounded="lg">
                    <v-window show-arrows>
                      <v-window-item v-for="n in item?.picture" :key="`card-${n}`">
                        <v-img
                          :lazy-src="`https://picsum.photos/10/6?image=${i * n * 5 + 10}`"
                          :src="n.path"
                          height="205"
                          width="400"
                          cover
                        ></v-img>
                      </v-window-item>
                    </v-window>
                    <v-card-title>{{ item?.name }}</v-card-title>
                    <v-card-subtitle class="pt-4"
                      >รหัสหนังสือ : {{ item?.no }}</v-card-subtitle
                    >
                    <v-card-text>
                      <div class="text-caption mb-2">รายละเอียด</div>
                      <div class="mb-2">{{ item?.detail ? item?.detail : "-" }}</div>
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
                          >{{ cate.name }}</v-chip
                        >
                      </template>
                    </v-card-text>

                    <v-card-actions>
                      <div class="d-flex flex-column ga-2 w-100">
                        <div class="d-flex justify-end align-center">
                          <v-icon icon="mdi-currency-usd" size="small"></v-icon>
                          <div>50</div>
                        </div>
                        <div class="d-flex justify-space-between">
                          <v-btn text="Share">รายละเอียด</v-btn>
                          <v-btn
                            class="ms-auto"
                            color="primary"
                            text="Explore"
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
</template>

<script setup>
import { ref, defineProps, onMounted } from "vue";
import { useDisplay } from "vuetify";
import api from "/utils/axios";
const tab = ref(null);
const catelists = ref([]);
const getList = async () => {
  let { data } = await api.get("/api/category/getList");
  if (data?.data.length > 0) {
    catelists.value = data.data;
  }
};
const { mobile } = useDisplay();

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
onMounted(() => {
  getList();
  getListProduct();
});
</script>
