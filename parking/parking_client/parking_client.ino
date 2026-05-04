#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <ArduinoJson.h>
#include <ESP32Servo.h>
#include <HTTPClient.h>
#include <MFRC522.h>
#include <SPI.h>
#include <WiFi.h>
#include <Wire.h>

// ============ CONFIGURATION ============
const char *ssid = "ZTE_2.4G_mbb3NT";
const char *password = "DPXhK37A";
const char *server_url = "http://192.168.1.9/parking/";

// ============ PINS ============
#define SERVO_PIN 25
#define SS_PIN 5
#define RST_PIN 4
#define BUZZER 27
#define TRIG1 13
#define ECHO1 34
#define TRIG2 32
#define ECHO2 33
#define TRIG_EXIT 16
#define ECHO_EXIT 17
#define LED_SLOT1 26
#define LED_SLOT2 2
#define LED_HIJAU 15
#define LED_MERAH 14

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

// ============ DEVICES ============
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
MFRC522 rfid(SS_PIN, RST_PIN);
Servo palang;

// ============ SETTINGS ============
const int SLOT_THRESHOLD = 15; // cm - car detected
const int EXIT_THRESHOLD = 15; // cm - car at exit
const unsigned long GATE_TIME = 2000; // Auto-close after 2 seconds
const unsigned long PARK_TIMEOUT = 60000; // 60s to park
const unsigned long DENIED_TIME = 3000;
const unsigned long SENSOR_INTERVAL = 300;
const unsigned long SLOT_CONFIRM_TIME = 900;
const unsigned long SENSOR_SYNC_INTERVAL = 1000;
const unsigned long SENSOR_SYNC_HEARTBEAT = 3000; // 3s heartbeat for faster ONLINE detection
const unsigned long EXIT_DEBOUNCE = 5000;

// ============ STATE ============
int parkedCount = 0;
bool slot_occupied[2] = {false, false};
String slot_name[2] = {"", ""};
String slot_plate[2] = {"", ""};

bool waitingForPark = false;
unsigned long waitParkStart = 0;
String waitName = "";
String waitPlate = "";

bool gateOpen = false;
unsigned long gateOpenTime = 0;
bool showDeniedScreen = false;
unsigned long deniedStartTime = 0;
unsigned long lastSensorCheck = 0;
unsigned long lastExitCheck = 0;
unsigned long lastGatePoll = 0;
unsigned long lastSensorSync = 0;
unsigned long lastSensorHeartbeat = 0;
bool sensorDirty = true;
bool pending_slot_state[2] = {false, false};
unsigned long pending_slot_since[2] = {0, 0};
bool wait_slot_armed[2] = {false, false};
const unsigned long GATE_POLL_INTERVAL = 3000;

// ============ BUZZER ============
void buzz(int count, int onMs, int offMs) {
  for (int i = 0; i < count; i++) {
    digitalWrite(BUZZER, HIGH);
    delay(onMs);
    digitalWrite(BUZZER, LOW);
    if (i < count - 1)
      delay(offMs);
  }
}

// ============ ULTRASONIC ============
long readDist(int trig, int echo) {
  digitalWrite(trig, LOW);
  delayMicroseconds(2);
  digitalWrite(trig, HIGH);
  delayMicroseconds(10);
  digitalWrite(trig, LOW);
  long d = pulseIn(echo, HIGH, 25000);
  return (d == 0) ? 999 : d * 0.034 / 2;
}

// ============ OLED SCREENS ============
void screenIdle() {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("SMART PARKING SYSTEM");
  display.drawLine(0, 10, 128, 10, WHITE);

  // Slot 1
  display.setCursor(0, 14);
  display.print("S1:");
  if (slot_occupied[0]) {
    display.print("[");
    display.print(slot_name[0].substring(0, 8));
    display.println("]");
  } else {
    display.println(" KOSONG");
  }

  // Slot 2
  display.setCursor(0, 26);
  display.print("S2:");
  if (slot_occupied[1]) {
    display.print("[");
    display.print(slot_name[1].substring(0, 8));
    display.println("]");
  } else {
    display.println(" KOSONG");
  }

  display.drawLine(0, 36, 128, 36, WHITE);
  display.setCursor(0, 40);
  display.print("Terisi: ");
  display.print(parkedCount);
  display.println("/2");

  display.setCursor(0, 52);
  if (parkedCount >= 2) {
    display.println(">> PARKIR PENUH! <<");
  } else {
    display.println("Scan kartu RFID...");
  }
  display.display();
}

void screenChecking() {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(15, 10);
  display.println("MEMPROSES...");
  display.setTextSize(2);
  display.setCursor(25, 28);
  display.println("WAIT");
  display.display();
}

void screenGranted(String name) {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(">> SELAMAT DATANG <<");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setTextSize(2);
  display.setCursor(0, 14);
  display.println("MASUK!");
  display.setTextSize(1);
  display.setCursor(0, 38);
  display.println(name);
  display.setCursor(0, 50);
  display.println("Silahkan parkir...");
  display.display();
}

void screenDenied(String reason) {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("!! AKSES DITOLAK !!");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setTextSize(2);
  display.setCursor(5, 18);
  display.println("DENIED");
  display.setTextSize(1);
  display.setCursor(0, 42);
  display.println(reason);
  display.display();
}

void screenFull() {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("!! PARKIR PENUH !!");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setTextSize(2);
  display.setCursor(10, 18);
  display.println("FULL!");
  display.setTextSize(1);
  display.setCursor(0, 42);
  display.println("Slot 1: TERISI");
  display.setCursor(0, 52);
  display.println("Slot 2: TERISI");
  display.display();
}

void screenWaitPark() {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("CARI SLOT PARKIR");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setCursor(0, 16);
  display.print("Driver: ");
  display.println(waitName.substring(0, 10));
  display.setCursor(0, 30);
  display.print("S1: ");
  display.println(slot_occupied[0] ? "TERISI" : ">> KOSONG <<");
  display.setCursor(0, 42);
  display.print("S2: ");
  display.println(slot_occupied[1] ? "TERISI" : ">> KOSONG <<");
  display.setCursor(0, 54);
  display.println("Parkir di slot kosong");
  display.display();
}

void screenParked(int slotNum, String name) {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println("MOBIL TERPARKIR!");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setTextSize(2);
  display.setCursor(0, 16);
  display.print("SLOT ");
  display.println(slotNum);
  display.setTextSize(1);
  display.setCursor(0, 38);
  display.println(name);
  display.setCursor(0, 52);
  display.print("Terisi: ");
  display.print(parkedCount);
  display.println("/2");
  display.display();
}

void screenExitDone(String name, int fee) {
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(0, 0);
  display.println(">> SELAMAT JALAN <<");
  display.drawLine(0, 10, 128, 10, WHITE);
  display.setTextSize(2);
  display.setCursor(10, 14);
  display.println("BYE!");
  display.setTextSize(1);
  display.setCursor(0, 36);
  display.println(name);
  if (fee > 0) {
    display.setCursor(0, 48);
    display.print("Biaya: Rp ");
    display.println(fee);
  }
  display.display();
}

// ============ SERVER COMMUNICATION ============
String urlEncode(const String &value) {
  String encoded = "";
  char bufHex[4];
  for (size_t i = 0; i < value.length(); i++) {
    char c = value.charAt(i);
    if ((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') ||
        (c >= '0' && c <= '9') || c == '-' || c == '_' || c == '.' ||
        c == '~') {
      encoded += c;
    } else if (c == ' ') {
      encoded += '+';
    } else {
      snprintf(bufHex, sizeof(bufHex), "%%%02X", (unsigned char)c);
      encoded += bufHex;
    }
  }
  return encoded;
}

void refreshParkedCount() {
  parkedCount = (slot_occupied[0] ? 1 : 0) + (slot_occupied[1] ? 1 : 0);
}

void refreshSlotLeds() {
  digitalWrite(LED_SLOT1, slot_occupied[0] ? HIGH : LOW);
  digitalWrite(LED_SLOT2, slot_occupied[1] ? HIGH : LOW);
}

void setSlotState(int idx, bool occupied, const String &name = "",
                  const String &plate = "") {
  slot_occupied[idx] = occupied;
  if (occupied) {
    slot_name[idx] = name.length() ? name : "UNKNOWN";
    slot_plate[idx] = plate.length() ? plate : "-";
  } else {
    slot_name[idx] = "";
    slot_plate[idx] = "";
  }
  refreshParkedCount();
  refreshSlotLeds();
  sensorDirty = true;
}

void resetWaitSlotArming() {
  for (int i = 0; i < 2; i++) {
    wait_slot_armed[i] = false;
    pending_slot_state[i] = false;
    pending_slot_since[i] = millis();
  }
}

String stripUtf8Bom(const String &payload) {
  String out = payload;
  if (out.length() >= 3 && (uint8_t)out[0] == 0xEF && (uint8_t)out[1] == 0xBB &&
      (uint8_t)out[2] == 0xBF) {
    out = out.substring(3);
  }
  return out;
}

bool syncSensors() {
  if (WiFi.status() != WL_CONNECTED)
    return false;
  HTTPClient http;
  http.setTimeout(3000);
  http.begin(String(server_url) + "api_update_sensors.php");
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String body = "s1=" + String(slot_occupied[0] ? 1 : 0) +
                "&s2=" + String(slot_occupied[1] ? 1 : 0) +
                "&s1_name=" + urlEncode(slot_name[0]) +
                "&s1_plate=" + urlEncode(slot_plate[0]) +
                "&s2_name=" + urlEncode(slot_name[1]) +
                "&s2_plate=" + urlEncode(slot_plate[1]);
  int code = http.POST(body);
  
  if (code == 200) {
    String rawPayload = http.getString();
    String payload = stripUtf8Bom(rawPayload);
    
    // SYNC BACK: Update local slot_name if server has better data
    #if ARDUINOJSON_VERSION_MAJOR >= 7
        JsonDocument doc;
    #else
        DynamicJsonDocument doc(2048);
    #endif
    DeserializationError err = deserializeJson(doc, payload);
    if (!err && doc.containsKey("slots")) {
      JsonArray slots = doc["slots"].as<JsonArray>();
      for (JsonVariant v : slots) {
        int sid = v["id"].as<int>();
        int idx = sid - 1;
        if (idx >= 0 && idx < 2) {
          String sName = v["name"].as<String>();
          String sPlate = v["plate"].as<String>();
          // Only update if current is generic or empty
          if (slot_occupied[idx] && (slot_name[idx] == "" || slot_name[idx] == "Walk-in" || slot_name[idx] == "UNKNOWN")) {
            if (sName != "" && sName != "UNKNOWN" && sName != "Walk-in") {
               slot_name[idx] = sName;
               slot_plate[idx] = sPlate;
               Serial.println("Sync: Slot " + String(sid) + " assigned to " + sName);
            }
          }
        }
      }
    }

    sensorDirty = false;
    lastSensorHeartbeat = millis();
    http.end();
    return true;
  }
  http.end();
  Serial.println("SYNC FAIL HTTP: " + String(code));
  return false;
}

void syncSensorsIfNeeded(bool force = false) {
  unsigned long now = millis();
  bool dirtyDue = sensorDirty && (now - lastSensorSync >= SENSOR_SYNC_INTERVAL);
  bool heartbeatDue =
      (!sensorDirty) && (now - lastSensorHeartbeat >= SENSOR_SYNC_HEARTBEAT);
  if (force || dirtyDue || heartbeatDue) {
    lastSensorSync = now;
    syncSensors();
  }
}

bool apiCheckAccess(String uid, String &outName, String &outPlate,
                    String &outMsg) {
  if (WiFi.status() != WL_CONNECTED)
    return false;

  bool granted = false;
  HTTPClient http;
  http.setTimeout(5000); // Diperpanjang menjadi 5 detik untuk mencegah timeout saat server idle (Cold Start)
  http.begin(String(server_url) + "api_check_access.php");
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  int code = http.POST("uid=" + uid);
  Serial.println("HTTP:" + String(code));

  if (code == 200) {
    String rawPayload = http.getString();
    String payload = stripUtf8Bom(rawPayload);
    Serial.println("Resp:" + payload);

#if ARDUINOJSON_VERSION_MAJOR >= 7
    JsonDocument doc;
#else
    DynamicJsonDocument doc(1024);
#endif
    DeserializationError err = deserializeJson(doc, payload);

    if (!err) {
      granted = doc["access"].as<bool>();
      outName = doc["name"].as<String>();
      outPlate = doc["plate"].as<String>();
      outMsg = doc["message"].as<String>();
      Serial.println("Parsed: access=" + String(granted) + " name=" + outName);
    } else {
      Serial.println("JSON err:" + String(err.c_str()));
      // Fallback string parsing
      if (payload.indexOf("\"access\":true") >= 0 ||
          payload.indexOf("\"access\": true") >= 0) {
        granted = true;
        int ni = payload.indexOf("\"name\":\"");
        if (ni >= 0) {
          ni += 8;
          int ne = payload.indexOf("\"", ni);
          outName = payload.substring(ni, ne);
        }
        int pi = payload.indexOf("\"plate\":\"");
        if (pi >= 0) {
          pi += 9;
          int pe = payload.indexOf("\"", pi);
          outPlate = payload.substring(pi, pe);
        }
        int mi = payload.indexOf("\"message\":\"");
        if (mi >= 0) {
          mi += 11;
          int me = payload.indexOf("\"", mi);
          outMsg = payload.substring(mi, me);
        }
        Serial.println("Fallback: name=" + outName);
      } else {
        // Extract denied message
        int mi = payload.indexOf("\"message\":\"");
        if (mi >= 0) {
          mi += 11;
          int me = payload.indexOf("\"", mi);
          outMsg = payload.substring(mi, me);
        }
      }
    }
  }

  http.end();
  delay(30);
  return granted;
}

bool apiAutoExit(const String &expectedName, const String &expectedPlate,
                 String &outName, int &outFee) {
  if (WiFi.status() != WL_CONNECTED)
    return false;

  bool success = false;
  HTTPClient http;
  http.setTimeout(5000); // Diperpanjang menjadi 5 detik
  http.begin(String(server_url) + "api_auto_exit.php");
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String postBody = "exit=1";
  if (expectedName.length() > 0) {
    postBody += "&expected_name=" + urlEncode(expectedName);
  }
  if (expectedPlate.length() > 0) {
    postBody += "&expected_plate=" + urlEncode(expectedPlate);
  }

  int code = http.POST(postBody);
  Serial.println("Exit HTTP:" + String(code));

  if (code == 200) {
    String rawPayload = http.getString();
    String payload = stripUtf8Bom(rawPayload);
    Serial.println("Exit Resp:" + payload);

#if ARDUINOJSON_VERSION_MAJOR >= 7
    JsonDocument doc;
#else
    DynamicJsonDocument doc(1024);
#endif
    DeserializationError err = deserializeJson(doc, payload);

    if (!err && doc["success"].as<bool>()) {
      success = true;
      outName = doc["name"].as<String>();
      if (outName.length() == 0)
        outName = doc["message"].as<String>();
      outFee = doc["fee"].as<int>();
    } else if (payload.indexOf("\"success\":true") >= 0 ||
               payload.indexOf("\"success\": true") >= 0) {
      success = true;
      int mi = payload.indexOf("\"message\":\"");
      if (mi >= 0) {
        mi += 11;
        int me = payload.indexOf("\"", mi);
        outName = payload.substring(mi, me);
      }
      int fi = payload.indexOf("\"fee\":");
      if (fi >= 0) {
        fi += 6;
        int fe = payload.indexOf(",", fi);
        if (fe < 0)
          fe = payload.indexOf("}", fi);
        outFee = payload.substring(fi, fe).toInt();
      }
    }
  }

  http.end();
  delay(30);
  return success;
}

// ============ GATE ============
void openGate() {
  palang.write(90); // Buka palang (90 derajat)
  gateOpen = true;
  gateOpenTime = millis();
  digitalWrite(LED_MERAH, LOW);
  digitalWrite(LED_HIJAU, HIGH);
  Serial.println("GATE OPEN");
}

void closeGate() {
  palang.write(0); // Tutup palang (0 derajat)
  buzz(1, 120, 0); // gate close beep
  gateOpen = false;
  digitalWrite(LED_HIJAU, LOW);
  digitalWrite(LED_MERAH, HIGH);
  Serial.println("GATE CLOSED");
}

// ============ SLOT MANAGEMENT ============
void hydrateSlotsFromSensors() {
  for (int i = 0; i < 2; i++) {
    long d = readDist(i == 0 ? TRIG1 : TRIG2, i == 0 ? ECHO1 : ECHO2);
    bool occupied = (d > 0 && d <= SLOT_THRESHOLD);
    slot_occupied[i] = occupied;
    slot_name[i] = occupied ? "UNKNOWN" : "";
    slot_plate[i] = occupied ? "-" : "";
    pending_slot_state[i] = occupied;
    pending_slot_since[i] = millis();
  }
  refreshParkedCount();
  refreshSlotLeds();
  sensorDirty = true;
  resetWaitSlotArming();
}

void armWaitingSlots() {
  for (int i = 0; i < 2; i++) {
    if (slot_occupied[i]) {
      wait_slot_armed[i] = false;
      continue;
    }
    wait_slot_armed[i] = true;
    pending_slot_state[i] = false;
    pending_slot_since[i] = millis();
  }
}

void detectParkingSlotWhileWaiting() {
  if (!waitingForPark)
    return;

  if (millis() - lastSensorCheck < SENSOR_INTERVAL)
    return;
  lastSensorCheck = millis();

  for (int i = 0; i < 2; i++) {
    if (!wait_slot_armed[i] || slot_occupied[i])
      continue;

    long d = readDist(i == 0 ? TRIG1 : TRIG2, i == 0 ? ECHO1 : ECHO2);
    bool sensedOccupied = (d > 0 && d <= SLOT_THRESHOLD);

    if (!sensedOccupied) {
      pending_slot_state[i] = false;
      pending_slot_since[i] = millis();
      continue;
    }

    if (!pending_slot_state[i]) {
      pending_slot_state[i] = true;
      pending_slot_since[i] = millis();
      continue;
    }

    if (millis() - pending_slot_since[i] < SLOT_CONFIRM_TIME)
      continue;

    setSlotState(i, true, waitName, waitPlate);
    waitingForPark = false;
    resetWaitSlotArming();

    Serial.println("PARKED: " + waitName + " in Slot " + String(i + 1));
    buzz(2, 100, 80);
    screenParked(i + 1, waitName);
    delay(2000);
    screenIdle();
    syncSensorsIfNeeded(true);
    return;
  }

  if (waitingForPark && millis() - waitParkStart > PARK_TIMEOUT) {
    Serial.println("Park timeout! No slot detected.");
    waitingForPark = false;
    resetWaitSlotArming();
    screenIdle();
  }
}

bool equalsIgnoreCase(const String &a, const String &b) {
  String aa = a;
  String bb = b;
  aa.toLowerCase();
  bb.toLowerCase();
  return aa == bb;
}

void clearSlotForExit(const String &rawExitName) {
  String exitName = rawExitName;
  exitName.trim();
  if (exitName.startsWith("Goodbye "))
    exitName = exitName.substring(8);
  exitName.trim();

  int target = -1;
  int physicallyEmptyLatched = -1;
  for (int i = 0; i < 2; i++) {
    if (!slot_occupied[i])
      continue;
    long d = readDist(i == 0 ? TRIG1 : TRIG2, i == 0 ? ECHO1 : ECHO2);
    if (d > SLOT_THRESHOLD) {
      physicallyEmptyLatched = i;
      break;
    }
  }

  for (int i = 0; i < 2; i++) {
    if (slot_occupied[i] && exitName.length() > 0 &&
        equalsIgnoreCase(slot_name[i], exitName)) {
      target = i;
      break;
    }
  }
  if (target < 0 && physicallyEmptyLatched >= 0) {
    target = physicallyEmptyLatched;
  }
  if (target < 0) {
    for (int i = 0; i < 2; i++) {
      if (slot_occupied[i]) {
        target = i;
        break;
      }
    }
  }

  if (target >= 0) {
    Serial.println("Clearing slot " + String(target + 1) + " (" +
                   slot_name[target] + ") after exit sensor");
    setSlotState(target, false);
    syncSensorsIfNeeded(true);
  } else {
    Serial.println("Exit detected but no occupied slot latched");
  }
}

bool getLikelyExitIdentity(String &candidateName, String &candidatePlate) {
  candidateName = "";
  candidatePlate = "";

  for (int i = 0; i < 2; i++) {
    if (!slot_occupied[i])
      continue;

    long d = readDist(i == 0 ? TRIG1 : TRIG2, i == 0 ? ECHO1 : ECHO2);
    if (d > SLOT_THRESHOLD) {
      candidateName = slot_name[i];
      candidatePlate = slot_plate[i];
      return true;
    }
  }
  return false;
}

// ============ SETUP ============
void setup() {
  Serial.begin(115200);
  Serial.println("\n=== SMART PARKING v4.0 FIXED ===");

  pinMode(BUZZER, OUTPUT);
  digitalWrite(BUZZER, LOW);
  pinMode(TRIG1, OUTPUT);
  pinMode(ECHO1, INPUT);
  pinMode(TRIG2, OUTPUT);
  pinMode(ECHO2, INPUT);
  pinMode(TRIG_EXIT, OUTPUT);
  pinMode(ECHO_EXIT, INPUT);
  pinMode(LED_HIJAU, OUTPUT);
  pinMode(LED_MERAH, OUTPUT);
  pinMode(LED_SLOT1, OUTPUT);
  pinMode(LED_SLOT2, OUTPUT);

  digitalWrite(LED_MERAH, HIGH);
  digitalWrite(LED_HIJAU, LOW);
  digitalWrite(LED_SLOT1, LOW);
  digitalWrite(LED_SLOT2, LOW);

  // Servo — gunakan timer 3 agar tidak konflik dengan SPI/I2C
  ESP32PWM::allocateTimer(3);
  palang.setPeriodHertz(50);
  palang.attach(SERVO_PIN, 544, 2400); // Standar microsecond range
  delay(100);

  // === SERVO TEST: buka-tutup sekali saat boot ===
  Serial.println("SERVO TEST: opening...");
  palang.write(90);
  delay(1000);
  Serial.println("SERVO TEST: closing...");
  palang.write(0);
  delay(1000);
  Serial.println("SERVO TEST: done");

  // OLED
  Wire.begin(21, 22);
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("OLED FAIL!");
  }
  display.clearDisplay();
  display.setTextColor(WHITE);
  display.setTextSize(1);
  display.setCursor(5, 20);
  display.println("Connecting WiFi...");
  display.display();

  // WiFi
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  int att = 0;
  while (WiFi.status() != WL_CONNECTED && att < 30) {
    delay(500);
    att++;
  }
  if (WiFi.status() == WL_CONNECTED)
    Serial.println("WiFi OK: " + WiFi.localIP().toString());
  else
    Serial.println("WiFi FAIL");

  // RFID — init SETELAH WiFi agar SPI stabil
  SPI.begin();
  rfid.PCD_Init();
  delay(200);
  rfid.PCD_DumpVersionToSerial(); // Tampilkan versi RFID reader
  Serial.println("RFID reader ready");

  // Read physical slots first, then sync to server.
  hydrateSlotsFromSensors();
  syncSensorsIfNeeded(true);
  buzz(1, 100, 0);
  delay(200);
  screenIdle();
  Serial.println("System ready!");
}

// ============ MAIN LOOP ============
void loop() {

  // --- 1. Auto-close gate ---
  if (gateOpen && millis() - gateOpenTime >= GATE_TIME) {
    closeGate();
    if (waitingForPark) {
      screenWaitPark();
    } else {
      screenIdle();
    }
  }

  // --- 2. Clear denied screen ---
  if (showDeniedScreen && millis() - deniedStartTime > DENIED_TIME) {
    showDeniedScreen = false;
    screenIdle();
  }

  // --- 3. WAIT FOR PARKING SLOT ASSIGNMENT ---
  detectParkingSlotWhileWaiting();

  // --- 4. RFID SCAN (PRIORITAS UTAMA — diletakkan SEBELUM exit sensor) ---
  if (!gateOpen && !waitingForPark && !showDeniedScreen) {
    if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
      // Build UID
      String uid = "";
      for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10)
          uid += "0";
        uid += String(rfid.uid.uidByte[i], HEX);
      }
      uid.toUpperCase();
      Serial.println("RFID: " + uid);

      // Cek apakah parkir penuh
      if (parkedCount >= 2) {
        screenFull();
        buzz(3, 80, 60);
        showDeniedScreen = true;
        deniedStartTime = millis();
      } else {
        screenChecking();

        // Check access via API
        String name = "", plate = "", msg = "";
        bool granted = apiCheckAccess(uid, name, plate, msg);

        if (granted) {
          Serial.println("GRANTED: " + name);
          screenGranted(name);
          buzz(2, 100, 80);
          delay(300);
          openGate();

          // Start waiting for car to park
          waitingForPark = true;
          waitParkStart = millis();
          waitName = name;
          waitPlate = plate;
          armWaitingSlots();
        } else {
          Serial.println("DENIED: " + msg);
          screenDenied(msg);
          buzz(3, 80, 60);
          showDeniedScreen = true;
          deniedStartTime = millis();
        }
      }

      rfid.PICC_HaltA();
      rfid.PCD_StopCrypto1();
    }
  }

  // --- 5. EXIT SENSOR ---
  if (parkedCount > 0 && !gateOpen && !waitingForPark && !showDeniedScreen) {
    if (millis() - lastExitCheck > EXIT_DEBOUNCE) {
      long dExit = readDist(TRIG_EXIT, ECHO_EXIT);

      if (dExit > 0 && dExit <= EXIT_THRESHOLD) {
        delay(80);
        long dExitConfirm = readDist(TRIG_EXIT, ECHO_EXIT);
        if (dExitConfirm > 0 && dExitConfirm <= EXIT_THRESHOLD) {
          lastExitCheck = millis();
          Serial.println("EXIT DETECTED! d=" + String(dExitConfirm));
          screenChecking();

          String exitName = "";
          int exitFee = 0;
          String expectedName = "";
          String expectedPlate = "";

          if (getLikelyExitIdentity(expectedName, expectedPlate)) {
            Serial.println("Exit candidate from slot: " + expectedName + " / " +
                           expectedPlate);
          }

          if (apiAutoExit(expectedName, expectedPlate, exitName, exitFee)) {
            Serial.println("Exit OK: " + exitName + " fee=" + String(exitFee));
            screenExitDone(exitName, exitFee);
            buzz(2, 100, 80);
            delay(300);
            openGate();
            clearSlotForExit(exitName);
          } else {
            Serial.println("Exit API: no active parking");
            screenDenied("Tidak ada data");
            buzz(3, 80, 60);
            showDeniedScreen = true;
            deniedStartTime = millis();
          }
        }
      }
    }
  }

  // --- 6. POLL GATE COMMANDS FROM SERVER ---
  if (millis() - lastGatePoll > GATE_POLL_INTERVAL) {
    lastGatePoll = millis();
    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.setTimeout(1000);
      http.begin(String(server_url) + "api_gate.php?poll=1");
      int code = http.GET();
      if (code == 200) {
        String payload = http.getString();
        if (payload.indexOf("\"OPEN\"") >= 0) {
          Serial.println("REMOTE: OPEN GATE");
          if (!gateOpen) {
            buzz(1, 150, 0);
            openGate();
            
            // FIX: Activate sensors for manual entry!
            waitingForPark = true;
            waitParkStart = millis();
            waitName = "Walk-in";
            waitPlate = "-";
            armWaitingSlots();
          }
        } else if (payload.indexOf("\"CLOSE\"") >= 0) {
          Serial.println("REMOTE: CLOSE GATE");
          if (gateOpen) {
            closeGate();
            screenIdle();
          }
        } else if (payload.indexOf("\"REBOOT\"") >= 0) {
          Serial.println("REMOTE: REBOOT DEVICE");
          if (gateOpen) closeGate();
          delay(500);
          ESP.restart();
        }
      }
      http.end();
    }
  }

  // --- 7. PERIODIC SENSOR SYNC ---
  syncSensorsIfNeeded();

  // --- 8. DEBUG SENSORS (Print every 2 seconds) ---
  static unsigned long lastDebugTime = 0;
  if (millis() - lastDebugTime > 2000) {
    lastDebugTime = millis();
    long d1 = readDist(TRIG1, ECHO1);
    long d2 = readDist(TRIG2, ECHO2);
    Serial.print("--- DEBUG SENSOR --- SLOT 1 (Pin 13,12): ");
    Serial.print(d1);
    Serial.print(" cm | SLOT 2: ");
    Serial.print(d2);
    Serial.println(" cm");
  }

  delay(10);
}
