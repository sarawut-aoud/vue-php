<template>
  <v-footer border>
    <div class="d-flex ga-2 align-start w-100 flex-column px-10 pt-5">
      <div v-if="user" class="d-xl-flex w-100 ga-2 align-start ">
        <div class="d-flex ga-2 w-100 flex-column">
          <div>ช่องทางการชำระเงิน / Delivery</div>
          <div class="d-xl-flex ga-10">
            <div class="d-flex flex-column ga-2">
              <div class="d-flex align-center">
                <v-img
                  width="70"
                  height="70"
                  border=""
                  :src="'/api/assets/9359415.png'"
                ></v-img>
                <div class="w-100 ms-5">Mobile Banking</div>
              </div>
              <div class="d-flex align-center">
                <v-img
                  width="70"
                  height="70"
                  :src="'/api/assets/PromptPay-logo.png'"
                ></v-img>
                <div class="w-100 ms-5">Promptpay</div>
              </div>
            </div>
            <v-divider vertical></v-divider>
            <div class="d-flex flex-column ga-2">
              <div class="d-flex align-center">
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/api/assets/delivery.jpg'"
                ></v-img>
                <div class="w-100 ms-5">
                  <div>ไปรษณีย์ไทย</div>
                  <div style="font-size: 12px;" class="text-grey-darken-1">ค่าส่งเริ่มต้น 35 บาท</div>
                </div>
              </div>
              <div class="d-flex align-center ga-2">
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/api/assets/flash.png'"
                ></v-img>
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/api/assets/kerry.png'"
                ></v-img>
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/api/assets/0600-ems-vietnam.png'"
                ></v-img>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex ga-2 w-100 flex-column mt-2">
          <div>ติดตามข่าวสารได้ที่</div>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex align-center">
              <a href="https://line.me/ti/p/@690gxwnq" target="_blank">
                <v-img
                  width="70"
                  height="70"
                  border=""
                  :src="'/api/assets/line.png'"
                ></v-img
              ></a>
              <div class="w-100 ms-5">
                <a href="https://line.me/ti/p/@690gxwnq" target="_blank">@690gxwnq</a>
              </div>
            </div>
            <div class="d-flex align-center">
              <a href="https://www.tiktok.com/@reread.co?lang=en/" target="_blank">
                <v-img
                  width="70"
                  height="70"
                  border=""
                  :src="'/api/assets/tiktok.png'"
                ></v-img
              ></a>
              <div class="w-100 ms-5">
                <a href="https://www.tiktok.com/@reread.co?lang=en/" target="_blank"
                  >Reread.,Co
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <v-divider v-if="user" class="w-100"></v-divider>
      <div class="w-100 d-flex justify-end ga-2 align-end">
        <div>บริษัท รีรี้ด จำกัด (Re Read Co., Ltd.)</div>
        <div>1361/315 ถนนพหลโยธิน</div>
        <div>แขวงเสนานิคม เขตจตุจักร กรุงเทพมหานคร 10900</div>
      </div>
    </div>
  </v-footer>
</template>

<script setup>
import { ref, onMounted, } from "vue";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { useCookie } from "@/composables/useCookie";
import { useJWT } from "@/composables/useJWT";
const { deleteItem, isItem } = useLocalStorage();
const { deleteCookie, getCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const user = ref(true);

onMounted(() => {
  if (globalitem.value?.n == "admin") user.value = false;
});
</script>
