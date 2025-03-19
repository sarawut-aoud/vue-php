<script setup>
import { ref, defineProps, onMounted, inject } from "vue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import api from "/utils/axios";
import { Notivue, Notification, push } from "notivue";

const { getCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const getCountCart = inject("getCountCart");

const props = defineProps({
  order: {
    type: String,
    require: false,
  },
  dialog: Array,
  loadData: Function,
});

const qrcode = ref(null);

const selectedFiles = ref([]);
const fileInput = ref(null);
// 📌 ฟังก์ชันเมื่อผู้ใช้เลือกไฟล์
const onFileChange = (event) => {
  selectedFiles.value = [];
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        selectedFiles.value.push({ file, preview: e.target.result });
      };
      reader.readAsDataURL(file);
    }
  });
};

// 📌 ฟังก์ชันลบไฟล์ที่เลือก
const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

// 📌 อ้างอิง input file แบบซ่อน
const openFilePicker = () => {
  fileInput.value.click();
};
const loadingUpload = ref(false);
const payment_method = ref(null);

const clickUpload = async () => {
  loadingUpload.value = true;
  if (!selectedFiles.value) return;
  const files = selectedFiles.value.map((e) => e.file);
  const formData = new FormData();
  if (!payment_method.value) {
    Swal.fire({
      title: "เลือกวิธีการชำระก่อน !!!",
      icon: "error",
      draggable: true,
    });
    loadingUpload.value = false;
    return;
  }

  formData.append(`images[]`, files[0]);
  formData.append("id", options.value?.id);
  formData.append("payment_method", payment_method.value);
  formData.append("csrf_token_ci_gen", getCookie("csrf_cookie_ci_gen"));
  try {
    const response = await api.post("/api/orders/paymentPaid", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    console.log("Upload success:", response.data);
    push.success("ยืนยันคำสั่งซื้อสำเร็จ!");
    getOrderPayment();
  } catch (error) {
    console.error("Upload failed:", error);
    push.error("เกิดข้อผิดพลาดในการอัปโหลด");
  }
  setTimeout(async () => {
    loadingUpload.value = false;
    if (props.dialog.value) props.dialog.value = false;

    await props.loadData();
  }, 300);
};

const loadingOrder = ref(false);
const options = ref(null);
const getOrderPayment = async () => {
  loadingOrder.value = true;
  let { data } = await api.get("/api/Orders/getOrderPayment", {
    params: {
      order_number: props.order,
    },
  });
  options.value = data?.options;
  setTimeout(async () => {
    loadingOrder.value = false;
    await props.loadData();
  }, 500);
};
const itemMethod = ref([
  { title: "Qr-Code Promtpay", value: "promtpay" },
  // { title: "โอนชำระผ่านธนาคาร", value: "bank_tranfer" },
]);
onMounted(() => {
  getSetting();
  getOrderPayment();
});
</script>
<template>
  <div class="d-flex ga-2 w-100 pa-2">
    <div class="w-100 d-flex flex-column justify-start">
      <div class="text-h5 text-center">เลือกวิธีการชำระเงิน</div>
      <div>
        <v-select
          v-model="payment_method"
          :items="itemMethod"
          variant="solo"
          density="comfortable"
          placeholder="เลือกวิธีการชำระเงิน"
        ></v-select>
      </div>

      <template v-if="payment_method == 'promtpay'">
        <div style="width: 500px" class="mx-auto">
          <v-img cover src="/src/assets/IMG_2027.png"></v-img>
        </div>
      </template>
      <template v-if="payment_method == 'bank_tranfer'"> </template>
    </div>

    <div class="w-100 d-flex flex-column">
      <div class="d-flex flex-column align-end ga-2 w-100 pa-2 text-h5">
        <div class="d-flex justify-space-between w-100">
          <div class="w-100">รวมราคาสินค้า</div>
          <div class="w-100 text-end">{{ options?.total_price }} บาท</div>
        </div>
        <div class="d-flex justify-space-between w-100">
          <div class="w-100">รวมส่วนลด</div>
          <div class="w-100 text-end">{{ options?.discount }} บาท</div>
        </div>
        <div class="d-flex justify-space-between w-100">
          <div class="w-100">ค่าจัดส่ง</div>
          <div class="w-100 text-end">{{ options?.delivery_amount }} บาท</div>
        </div>

        <div class="d-flex justify-space-between w-100">
          <div class="w-100">รวมราคาสุทธิ</div>
          <div class="w-100 text-end">
            {{ options?.total_price + options?.delivery_amount }} บาท
          </div>
        </div>
      </div>
      <v-divider class="w-100"></v-divider>
      <div class="text-h5 text-center">อัปโหลดหลักฐานการชำระเงิน</div>
      <template v-if="selectedFiles.length > 0">
        <div class="position-relative">
          <v-btn
            @click="removeFile"
            size="small"
            class="position-absolute"
            style="bottom: 10px; z-index: 10; right: 25%; left: 25%"
            color="red"
            flat=""
            rounded="lg"
            >อัปโหลดหลักฐานใหม่</v-btn
          >
          <div style="width: 500px" class="mx-auto">
            <v-img rounded="lg" cover :src="selectedFiles[0]?.preview"></v-img>
          </div>
        </div>
      </template>
      <template v-else>
        <div class="upload-slip" @click="openFilePicker">
          <span class="mdi mdi-progress-upload"></span>
          <div class="font">อัปโหลดหลักฐานการชำระเงิน</div>
        </div>
        <input
          type="file"
          ref="fileInput"
          accept="image/*"
          hidden
          @change="onFileChange"
        />
      </template>
    </div>
  </div>
  <div class="pa-2 d-flex justify-end">
    <v-btn
      color="success"
      rounded="lg"
      :loading="loadingUpload"
      :disabled="loadingUpload"
      @click="clickUpload"
      >ยืนยันการชำระเงิน</v-btn
    >
  </div>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<style lang="scss" scoped>
.upload-slip {
  border: 1px dashed #464343;
  padding: 3em;
  height: 100%;
  border-radius: 1em;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  cursor: pointer;
  & > * {
    transition: all 300ms ease;
  }
  & .mdi {
    font-size: 50px;
  }
  &:hover {
    & .mdi {
      font-size: 60px;
    }
    & .font {
      font-size: 22px;
    }
  }
}
</style>
