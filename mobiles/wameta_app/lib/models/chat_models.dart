class DeviceModel {
  final int id;
  final String name;
  final String status;

  DeviceModel({required this.id, required this.name, required this.status});

  factory DeviceModel.fromJson(Map<String, dynamic> json) {
    return DeviceModel(
      id: json['id'],
      name: json['name'],
      status: json['status'],
    );
  }
}

class ConversationModel {
  final String contactNumber;
  final String contactName;
  final String lastMessage;
  final String lastTime;
  final int unreadCount;

  ConversationModel({
    required this.contactNumber,
    required this.contactName,
    required this.lastMessage,
    required this.lastTime,
    required this.unreadCount,
  });

  factory ConversationModel.fromJson(Map<String, dynamic> json) {
    return ConversationModel(
      contactNumber: json['contact_number'],
      contactName: json['contact_name'] ?? json['contact_number'],
      lastMessage: json['last_message'] ?? '',
      lastTime: json['last_time'] ?? '',
      unreadCount: json['unread_count'] ?? 0,
    );
  }
}

class MessageModel {
  final int id;
  final String body;
  final String direction;
  final String status;
  final String time;

  MessageModel({
    required this.id,
    required this.body,
    required this.direction,
    required this.status,
    required this.time,
  });

  factory MessageModel.fromJson(Map<String, dynamic> json) {
    return MessageModel(
      id: json['id'],
      body: json['message_body'],
      direction: json['direction'],
      status: json['status'],
      time: json['wa_timestamp'] ?? '',
    );
  }
}
