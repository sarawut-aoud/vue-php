<template>
  <v-footer border>
    <div class="d-flex ga-2 align-start w-100 flex-column px-10 pt-5">
      <div class="d-flex w-100 ga-2 align-start">
        <div class="d-flex ga-2 w-100 flex-column">
          <div>ช่องทางการชำระเงิน / Delivery</div>
          <div class="d-flex ga-10">
            <div class="d-flex flex-column ga-2">
              <div class="d-flex align-center">
                <v-img
                  width="70"
                  height="70"
                  border=""
                  :src="'/src/assets/9359415.png'"
                ></v-img>
                <div class="w-100 ms-5">Mobile Banking</div>
              </div>
              <div class="d-flex align-center">
                <v-img
                  width="70"
                  height="70"
                  :src="'/src/assets/PromptPay-logo.png'"
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
                  :src="'/src/assets/delivery.jpg'"
                ></v-img>
                <div class="w-100 ms-5">ไปรษณีย์ไทย</div>
              </div>
              <div class="d-flex align-center ga-2">
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/src/assets/flash.png'"
                ></v-img>
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/src/assets/kerry.png'"
                ></v-img>
                <v-img
                  width="70"
                  height="70"
                  class="border rounded-lg"
                  :src="'/src/assets/0600-ems-vietnam.png'"
                ></v-img>
              </div>
            </div>
          </div>
        </div>
        <div class="d-flex ga-2 w-100 flex-column">
          <div>ติดตามข่าวสารได้ที่</div>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex align-center">
              <a href="https://line.me/ti/p/@690gxwnq" target="_blank">
                <v-img
                  width="70"
                  height="70"
                  border=""
                  :src="'/src/assets/line.png'"
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
                  :src="'/src/assets/tiktok.png'"
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
      <v-divider class="w-100"></v-divider>
      <div class="w-100 d-flex justify-end ga-2 align-end">
        <div>บริษัท รีรี้ด จำกัด (Re Read Co., Ltd.)</div>
        <div>1361/315 ถนนพหลโยธิน</div>
        <div>แขวงเสนานิคม เขตจตุจักร กรุงเทพมหานคร 10900</div>
      </div>
    </div>
  </v-footer>
</template>

<script setup></script>
