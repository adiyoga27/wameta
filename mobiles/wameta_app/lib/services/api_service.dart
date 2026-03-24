import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  final String baseUrl = "http://10.0.2.2:8000/api"; // Default for Android Emulator
  final Dio _dio = Dio();
  final _storage = const FlutterSecureStorage();

  ApiService() {
    _dio.options.baseUrl = baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 10);
    _dio.options.receiveTimeout = const Duration(seconds: 10);
    
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        options.headers['Accept'] = 'application/json';
        return handler.next(options);
      },
    ));
  }

  Future<Response> login(String email, String password) async {
    return await _dio.post('/login', data: {
      'email': email,
      'password': password,
      'device_name': 'Mobile App',
    });
  }

  Future<Response> getDevices() async {
    return await _dio.get('/devices');
  }

  Future<Response> getConversations(int deviceId) async {
    return await _dio.get('/conversations/$deviceId');
  }

  Future<Response> getMessages(int deviceId, String contactNumber) async {
    return await _dio.get('/messages/$deviceId/$contactNumber');
  }

  Future<Response> sendMessage(int deviceId, String contactNumber, String message) async {
    return await _dio.post('/messages/send', data: {
      'device_id': deviceId,
      'contact_number': contactNumber,
      'message': message,
    });
  }

  Future<Response> getLabels() async {
    return await _dio.get('/labels');
  }

  Future<Response> assignLabels(int deviceId, String contactNumber, List<int> labelIds) async {
    return await _dio.post('/conversations/$deviceId/$contactNumber/labels', data: {
      'labels': labelIds,
    });
  }

  Future<Response> registerFcmToken(String token) async {
    return await _dio.post('/fcm-token', data: {'fcm_token': token});
  }
}
