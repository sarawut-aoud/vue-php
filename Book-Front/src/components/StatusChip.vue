<template>
  <v-chip size="small" :color="setText('color')">{{ setText("text") }}</v-chip>
</template>
<script setup>
import { defineProps, ref } from "vue";
const props = defineProps({
  status: String,
});
const setText = (item) => {
  let data = [];
  if (props.status == "pending") {
    data["text"] = "รอชำระเงิน";
    data["color"] = "yellow-darken-3";
  }
  if (props.status == "paid") {
    data["text"] = "กำลังตรวจสอบ";
    data["color"] = "primary";
  }
  if (props.status == "completed") {
    data["text"] = "ชำระเงินสำเร็จ";
    data["color"] = "success";
  }
  if (props.status == "canceled") {
    data["text"] = "ยกเลิกคำสั่งซื้อ";
    data["color"] = "error";
  }

  return data[item];
};
</script>
