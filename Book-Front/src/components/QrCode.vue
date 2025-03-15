<script setup>
import { ref, defineProps, onMounted, inject } from "vue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import api from "/utils/axios";
const { getCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const getCountCart = inject("getCountCart");

const props = defineProps({
  order: {
    type: String,
    require: false,
  },
});

const qrcode = ref(null);

const getSetting = async () => {
  let { data } = await api.get("/api/SettingQR/getSetting");
};

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
const clickUpload = async () => {
  loadingUpload.value = true;

  setTimeout(() => {
    loadingUpload.value = false;
  }, 300);
};

onMounted(() => {
  getSetting();
});
</script>
<template>
  <div class="d-flex ga-2 w-100 pa-2">
    <div class="w-100 d-flex flex-column">
      <div class="text-h5 text-center">QR-Code สำหรับชำระเงิน</div>
      <v-img cover src="https://cdn.vuetifyjs.com/images/parallax/material.jpg"></v-img>
    </div>

    <div class="w-100 d-flex flex-column">
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
          <v-img rounded="lg" cover :src="selectedFiles[0]?.preview"></v-img>
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
